<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $modulePermissions = collect(config('erp.modules', []))
            ->keys()
            ->flatMap(fn (string $module) => [
                "{$module}.view",
                "{$module}.manage",
            ]);

        $extra = collect(config('erp.extra_permissions', []));
        $allPermissions = $modulePermissions->merge($extra)->unique()->values();

        foreach ($allPermissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }

        $catalogPermissionNames = $allPermissions->all();

        foreach (config('erp.role_permissions', []) as $roleName => $permissions) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($permissions === ['*']) {
                $role->syncPermissions($catalogPermissionNames);

                continue;
            }

            $resolved = collect($permissions)
                ->filter(fn (string $p) => in_array($p, $catalogPermissionNames, true))
                ->values()
                ->all();

            $role->syncPermissions($resolved);
        }
    }
}