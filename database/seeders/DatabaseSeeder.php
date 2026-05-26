<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Kernel\Core;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(Core $c)
    {
        // 10 products with images (same as before)
        $productsData = [
            ['name' => 'Widget Pro', 'description' => 'High-performance widget', 'price' => 29.99, 'stock' => 120],
            ['name' => 'Gadget Max', 'description' => 'The ultimate gadget', 'price' => 49.99, 'stock' => 80],
            ['name' => 'Doodad Mini', 'description' => 'Compact doodad', 'price' => 9.99, 'stock' => 300],
            ['name' => 'Thingamajig', 'description' => 'Versatile thingamajig', 'price' => 19.99, 'stock' => 150],
            ['name' => 'Whatchamacallit', 'description' => 'Mysterious device', 'price' => 39.99, 'stock' => 60],
            ['name' => 'Gizmo X', 'description' => 'Next‑gen gizmo', 'price' => 79.99, 'stock' => 40],
            ['name' => 'Doohickey', 'description' => 'Essential doohickey', 'price' => 14.99, 'stock' => 200],
            ['name' => 'Thingy', 'description' => 'Simple thingy', 'price' => 4.99, 'stock' => 500],
            ['name' => 'Gadget Lite', 'description' => 'Lightweight gadget', 'price' => 24.99, 'stock' => 100],
            ['name' => 'Widget Basic', 'description' => 'Standard widget', 'price' => 12.99, 'stock' => 250],
        ];

        $products = [];
        foreach ($productsData as $idx => $data) {
            $data['created'] = now();
            $product = $c->create('product', $data);
            $products[] = $product;
            $imageId = $idx + 1;
            $c->create('media', [
                'type' => 'product',
                'link' => $product->id,
                'path' => "https://picsum.photos/id/{$imageId}/200/150",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Orders – using only allowed statuses
        $channels = ['web', 'email', 'phone', 'marketplace'];
        $statuses = ['pending', 'paid', 'shipped'];
        $customers = [
            'Alice Johnson', 'Bob Smith', 'Charlie Brown', 'Diana Prince',
            'Evan Wright', 'Fiona Green', 'George White', 'Hannah Black',
            'Ian Scott', 'Julia Adams', 'Kevin Lee', 'Laura Kim',
            'Mike Davis', 'Nina Patel', 'Oliver Chen', 'Paula Garcia',
            'Quinn Taylor', 'Rachel Moore', 'Steve Anderson', 'Tina Wong'
        ];

        for ($i = 0; $i < 30; $i++) {
            $date = Carbon::now()->subDays(rand(0, 30))->setTime(rand(8,20), rand(0,59), rand(0,59));
            $order = $c->create('order', [
                'customer' => $customers[array_rand($customers)],
                'channel' => $channels[array_rand($channels)],
                'date' => $date,
                'status' => $statuses[array_rand($statuses)],
            ]);
            $num = rand(1,4);
            for ($j = 0; $j < $num; $j++) {
                $product = $products[array_rand($products)];
                $qty = rand(1,5);
                $c->create('item', [
                    'order' => $order->id,
                    'product' => $product->id,
                    'quantity' => $qty,
                    'price' => $product->price,
                ]);
            }
        }
    }
}
