<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class MasterDataRegistry
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return config('master_data.resources', []);
    }

  public static function get(string $slug): array
    {
        $resource = self::all()[$slug] ?? null;

        if ($resource === null) {
            throw new InvalidArgumentException("Unknown master data resource [{$slug}].");
        }

        return $resource;
    }

    /**
     * @return class-string<Model>
     */
    public static function modelClass(string $slug): string
    {
        $class = self::get($slug)['model'];

        if (! is_subclass_of($class, Model::class)) {
            throw new InvalidArgumentException("Resource [{$slug}] model is invalid.");
        }

        return $class;
    }

    /**
     * @return list<array{slug: string, label: string, href: string}>
     */
    public static function hubItems(string $hub, ?\App\Models\User $user): array
    {
        $items = [];

        foreach (self::all() as $slug => $config) {
            if (($config['hub'] ?? null) !== $hub) {
                continue;
            }

            $viewPermission = $config['permissions']['view'] ?? null;

            if ($user !== null && $viewPermission !== null && ! $user->can($viewPermission)) {
                continue;
            }

            $items[] = [
                'slug' => $slug,
                'label' => $config['label'],
                'href' => route('master-data.index', $slug),
            ];
        }

        return $items;
    }

    public static function isReferenced(Model $record, string $slug): bool
    {
        foreach (self::get($slug)['references'] ?? [] as $reference) {
            $table = $reference['table'];
            $column = $reference['column'];

            if (! Schema::hasTable($table)) {
                continue;
            }

            if (\Illuminate\Support\Facades\DB::table($table)->where($column, $record->getKey())->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public static function mapRowForTable(Model $record, string $slug): array
    {
        $data = $record->toArray();

        if ($slug === 'machines') {
            return array_merge($data, [
                'machine_type_label' => $record->machineType?->name,
                'production_hall_label' => $record->productionHall?->name,
            ]);
        }

        if ($slug === 'designations') {
            return array_merge($data, [
                'department_label' => $record->department?->name,
            ]);
        }

        if (in_array($slug, ['material-categories', 'product-categories', 'expense-categories'], true)) {
            return array_merge($data, [
                'parent_label' => $record->parent?->name,
            ]);
        }

        return $data;
    }
}