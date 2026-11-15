<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryLedgerEntry;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryLedgerPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_movement_history_loads_the_warehouse_relation(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('owner-admin');

        $unit = UnitOfMeasurement::query()->create([
            'code' => 'KG',
            'name' => 'Kilogram',
            'symbol' => 'kg',
            'status' => 'active',
        ]);

        $category = MaterialCategory::query()->create([
            'code' => 'RAW',
            'name' => 'Raw plastic',
            'status' => 'active',
        ]);

        $warehouse = Warehouse::query()->create([
            'code' => 'WH1',
            'name' => 'Main store',
            'status' => 'active',
        ]);

        $material = Material::query()->create([
            'code' => 'PP-01',
            'name' => 'Polypropylene',
            'material_category_id' => $category->id,
            'unit_of_measurement_id' => $unit->id,
            'default_unit_cost' => 100,
            'reorder_level' => 10,
            'inventory_bucket' => 'raw',
            'status' => 'active',
        ]);

        $transaction = InventoryTransaction::query()->create([
            'transaction_number' => app(InventoryTransactionNumberGenerator::class)->next('purchase_receipt'),
            'type' => 'purchase_receipt',
            'status' => 'draft',
            'warehouse_id' => $warehouse->id,
            'transaction_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        $transaction->items()->create([
            'material_id' => $material->id,
            'quantity' => 10,
            'unit_cost' => 25,
            'total_value' => 250,
            'line_order' => 1,
        ]);

        app(InventoryPostingService::class)->post($transaction->fresh('items'), $user);

        $entry = InventoryLedgerEntry::query()->firstOrFail();
        $entry->load('warehouse');

        $this->assertTrue($entry->warehouse->is($warehouse));

        $this->actingAs($user)
            ->get(route('inventory.ledger.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Inventory/Ledger/Index')
                ->has('entries.data', 1)
                ->where('entries.data.0.warehouse.id', $warehouse->id)
                ->where('entries.data.0.warehouse.name', 'Main store')
                ->where('entries.data.0.material.name', 'Polypropylene'));
    }
}
