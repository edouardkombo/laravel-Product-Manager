<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Kernel\Core;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Faker\Factory as Faker;

class CoreTest extends TestCase
{
    use RefreshDatabase;

    protected Core $core;
    protected $fake;
    protected array $entities = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->core = app(Core::class);
        $this->fake = Faker::create();
        $this->entities = array_keys(config('core.entities', []));
    }

    private function randomData(string $entity): array
    {
        $rules = $this->core->rules($entity);
        $data = [];
        foreach ($rules as $field => $rule) {
            if (in_array($field, ['created_at', 'updated_at'])) continue;
            if (str_contains($rule, 'integer')) {
                $data[$field] = rand(1, 1000);
            } elseif (str_contains($rule, 'numeric')) {
                $data[$field] = rand(1, 1000) / 100;
            } elseif (str_contains($rule, 'string')) {
                $data[$field] = $this->fake->words(2, true);
            } elseif (preg_match('/in:([^,]+(?:,[^,]+)*)/', $rule, $m)) {
                $opts = explode(',', $m[1]);
                $data[$field] = $opts[array_rand($opts)];
            } elseif (preg_match('/exists:(\w+),(\w+)/', $rule, $m)) {
                $target = $m[1];
                $parent = $this->core->create($target, $this->randomData($target));
                $data[$field] = $parent->id;
            } elseif (str_contains($rule, 'email')) {
                $data[$field] = $this->fake->email;
            } else {
                $data[$field] = $this->fake->word;
            }
        }
        if ($entity === 'product' && !isset($data['created'])) {
            $data['created'] = now();
        }
        return $data;
    }

    private function corrupt(array &$data, string $field, string $rule): void
    {
        if (str_contains($rule, 'integer') || str_contains($rule, 'numeric')) {
            $data[$field] = 'notanumber';
        } elseif (str_contains($rule, 'string')) {
            $data[$field] = 12345;
        } elseif (str_contains($rule, 'in:')) {
            $data[$field] = 'invalid_option';
        } elseif (str_contains($rule, 'email')) {
            $data[$field] = 'not-an-email';
        } elseif (str_contains($rule, 'required')) {
            unset($data[$field]);
        } else {
            $data[$field] = null;
        }
    }

    /**
     * @testdox Create, read, update, verify, extract, delete (CURVED cycle) works for each entity
     */
    public function test_curved_cycle(): void
    {
        foreach ($this->entities as $entity) {
            $data = $this->randomData($entity);
            $obj = $this->core->create($entity, $data);
            $this->assertDatabaseHas($entity, ['id' => $obj->id]);

            $read = $this->core->read($entity, $obj->id);
            $this->assertEquals($obj->id, $read->id);

            $rules = $this->core->rules($entity);
            if (!empty($rules)) {
                $field = array_rand($rules);
                $new = $this->randomData($entity)[$field] ?? rand(1, 100);
                $this->core->update($entity, $obj->id, [$field => $new]);
                $this->assertDatabaseHas($entity, ['id' => $obj->id, $field => $new]);
                $this->assertTrue($this->core->verify($entity, $obj->id, $field, $new));
            }

            $ext = $this->core->extract($entity, ['where' => ['id' => $obj->id]]);
            $this->assertCount(1, $ext);
            $this->assertEquals($obj->id, $ext[0]['id']);

            $this->core->delete($entity, $obj->id);
            $this->assertDatabaseMissing($entity, ['id' => $obj->id]);
        }
    }

    /**
     * @testdox Invalid data triggers validation exceptions for each entity
     */
    public function test_invalid_data(): void
    {
        foreach ($this->entities as $entity) {
            $rules = $this->core->rules($entity);
            if (empty($rules)) continue;
            $good = $this->randomData($entity);
            $badField = array_rand($rules);
            $badRule = $rules[$badField];
            $this->corrupt($good, $badField, $badRule);
            try {
                $this->core->create($entity, $good);
                $this->fail("Expected validation exception for $entity field $badField");
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertTrue(true);
            }
        }
    }

    /**
     * @testdox Aggregation (sum, count) on numeric fields returns correct values for each entity
     */
    public function test_aggregation(): void
    {
        foreach ($this->entities as $entity) {
            $rules = $this->core->rules($entity);
            $numeric = array_filter($rules, fn($r) => str_contains($r, 'integer') || str_contains($r, 'numeric'));
            if (empty($numeric)) continue;
            for ($i = 0; $i < 3; $i++) {
                $this->core->create($entity, $this->randomData($entity));
            }
            $col = array_key_first($numeric);
            $sum = $this->core->extract($entity, ['aggregate' => ['sum' => $col]])['value'];
            $this->assertIsNumeric($sum);
            $cnt = $this->core->extract($entity, ['aggregate' => ['count' => true]])['value'];
            $this->assertEquals(3, $cnt);
        }
    }

    /**
     * @testdox CSV import (orders and items) works correctly
     */
    public function test_csv_import(): void
    {
        if (!in_array('product', $this->entities) || !in_array('order', $this->entities)) {
            $this->markTestSkipped('Product or order entity missing');
        }

        $products = [];
        for ($i = 0; $i < 5; $i++) {
            $products[] = $this->core->create('product', $this->randomData('product'));
        }

        $rows = [];
        for ($i = 0; $i < 10; $i++) {
            $rows[] = [
                'customer' => $this->fake->name,
                'product' => $products[array_rand($products)]->id,
                'quantity' => rand(1, 10),
            ];
        }

        foreach ($rows as $row) {
            $order = $this->core->create('order', ['customer' => $row['customer'], 'status' => 'pending']);
            $this->core->create('item', [
                'order' => $order->id,
                'product' => $row['product'],
                'quantity' => $row['quantity'],
                'price' => $this->core->read('product', $row['product'])->price,
            ]);
        }

        $this->assertDatabaseCount('order', 10);
        $this->assertDatabaseCount('item', 10);
    }
}
