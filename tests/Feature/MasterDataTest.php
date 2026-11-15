<?php

namespace Tests\Feature;

use App\Models\ProductionHall;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_storekeeper_can_view_but_not_create_production_halls(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('storekeeper');

        $this->actingAs($user)
            ->get(route('master-data.index', 'production-halls'))
            ->assertForbidden();

        $owner = User::factory()->create(['email_verified_at' => now()]);
        $owner->assignRole('owner-admin');

        $this->actingAs($owner)
            ->post(route('master-data.store', 'production-halls'), [
                'code' => 'H1',
                'name' => 'Main hall',
                'description' => null,
                'status' => 'active',
            ])
            ->assertRedirect(route('master-data.index', 'production-halls'));

        $this->assertDatabaseHas('production_halls', ['code' => 'H1', 'name' => 'Main hall']);
    }

    public function test_duplicate_code_is_rejected(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('owner-admin');

        ProductionHall::query()->create([
            'code' => 'H-DUP',
            'name' => 'Hall A',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('master-data.store', 'production-halls'), [
                'code' => 'H-DUP',
                'name' => 'Hall B',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_deactivate_sets_status_inactive(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('owner-admin');

        $hall = ProductionHall::query()->create([
            'code' => 'H-OFF',
            'name' => 'Hall Off',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post(route('master-data.deactivate', ['resource' => 'production-halls', 'record' => $hall->id]))
            ->assertRedirect();

        $this->assertDatabaseHas('production_halls', ['id' => $hall->id, 'status' => 'inactive']);
    }

    public function test_machines_halls_hub_requires_permission(): void
    {
        $operator = User::factory()->create(['email_verified_at' => now()]);
        $operator->assignRole('production-operator');

        $this->actingAs($operator)->get(route('erp.machines-halls'))->assertOk();

        $storekeeper = User::factory()->create(['email_verified_at' => now()]);
        $storekeeper->assignRole('storekeeper');

        $this->actingAs($storekeeper)->get(route('erp.machines-halls'))->assertForbidden();
    }
}