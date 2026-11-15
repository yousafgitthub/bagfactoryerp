<?php

namespace Tests\Feature;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $permissionsAfterFirst = Permission::count();
        $rolesAfterFirst = Role::count();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame($permissionsAfterFirst, Permission::count());
        $this->assertSame($rolesAfterFirst, Role::count());
        $this->assertTrue(Role::where('name', 'owner-admin')->exists());
        $this->assertTrue(Role::where('name', 'storekeeper')->exists());
    }
}