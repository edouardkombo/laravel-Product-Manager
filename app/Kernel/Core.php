<?php

namespace App\Kernel;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Event;

class Core
{
    protected array $models = [];
    protected array $rules = [];

    public function add(string $name, string $class, array $rules = []): void
    {
        $this->models[$name] = $class;
        if ($rules) $this->rules[$name] = $rules;
    }

    public function rules(string $entity): array
    {
        return $this->rules[$entity] ?? [];
    }

    public function create(string $entity, array $data): object
    {
        $this->validate($entity, $data);
        $class = $this->model($entity);
        $model = $class::create($data);
        Event::dispatch("{$entity}.created", $model);
        return $model;
    }

    public function read(string $entity, int $id): ?object
    {
        $class = $this->model($entity);
        return $class::find($id);
    }

    public function update(string $entity, int $id, array $data): object
    {
        $model = $this->read($entity, $id) ?? throw new \Exception('notfound');
        $this->validate($entity, $data, true);
        $model->update($data);
        Event::dispatch("{$entity}.updated", $model);
        return $model;
    }

    public function delete(string $entity, int $id): bool
    {
        $model = $this->read($entity, $id);
        if ($model) {
            $result = (bool) $model->delete();
            Event::dispatch("{$entity}.deleted", $id);
            return $result;
        }
        return false;
    }

    public function verify(string $entity, int $id, string $field, mixed $value): bool
    {
        $record = $this->read($entity, $id);
        return $record && ($record->$field ?? null) == $value;
    }

    /**
     * Declarative read projection.
     * Allowed spec keys: where, order, dir, take, skip, aggregate (sum, count, group).
     * When using group, 'take' is applied AFTER grouping to limit number of groups.
     */
    public function extract(string $entity, array $spec): array
    {
        $class = $this->model($entity);
        $query = $class::query();

        if (isset($spec['where'])) {
            foreach ($spec['where'] as $field => $val) $query->where($field, $val);
        }
        if (isset($spec['order'])) $query->orderBy($spec['order'], $spec['dir'] ?? 'asc');
        if (isset($spec['skip'])) $query->offset($spec['skip']);

        if (isset($spec['aggregate'])) {
            $agg = $spec['aggregate'];
            if (isset($agg['sum']) && !isset($agg['group'])) {
                return ['value' => $query->sum($agg['sum'])];
            }
            if (isset($agg['count']) && !isset($agg['group'])) {
                return ['value' => $query->count()];
            }
            if (isset($agg['group'])) {
                $groupBy = $agg['group'];
                $sumField = $agg['sum'] ?? null;
                if ($sumField) {
                    $rows = $query->selectRaw("{$groupBy}, SUM({$sumField}) as total")
                                  ->groupBy($groupBy)
                                  ->orderBy('total', 'desc');
                    if (isset($spec['take'])) {
                        $rows->limit($spec['take']);
                    }
                    return $rows->get()->toArray();
                } else {
                    $rows = $query->select($groupBy)->groupBy($groupBy);
                    if (isset($spec['take'])) $rows->limit($spec['take']);
                    return $rows->get()->toArray();
                }
            }
        }

        // No aggregation – apply take and get collection
        if (isset($spec['take'])) $query->limit($spec['take']);
        return $query->get()->toArray();
    }

    private function model(string $entity): string
    {
        return $this->models[$entity] ?? throw new \Exception("unknown entity: $entity");
    }

    private function validate(string $entity, array $data, bool $update = false): void
    {
        $rules = $this->rules[$entity] ?? [];
        if ($update) {
            $rules = array_map(fn($r) => str_replace('required', 'sometimes', $r), $rules);
        }
        Validator::make($data, $rules)->validate();
    }
}
