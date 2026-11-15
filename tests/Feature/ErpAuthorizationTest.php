<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_storekeeper_can_access_inventory_but_not_payroll(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('storekeeper');

        $this->actingAs($user)
            ->get(route('erp.inventory'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('erp.payroll'))
            ->assertForbidden();
    }

    public function test_production_operator_cannot_access_accounting(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('production-operator');

        $this->actingAs($user)
            ->get(route('erp.dana-production'))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('erp.accounting'))
            ->assertForbidden();
    }

    public function test_owner_has_full_module_access(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('owner-admin');

        $this->actingAs($user)
            ->get(route('erp.settings'))
            ->assertOk();
    }
}