<?php

namespace Tests\Feature\Dana;

use App\Models\DanaBatch;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\Machine;
use App\Models\MachineType;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\ProductionHall;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Dana\DanaBatchException;
use App\Services\Dana\DanaBatchPersistenceService;
use App\Services\Dana\DanaBatchPostingService;
use App\Services\Dana\DanaBatchWorkflowService;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DanaBatchWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Warehouse $rawWarehouse;

    private Warehouse $danaWarehouse;

    private Material $rawMaterial;

    private Material $danaMaterial;

    private Machine $machine;

    private ProductionHall $hall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('production-manager');

        $unit = UnitOfMeasurement::query()->create([
            'code' => 'KG',
            'name' => 'Kilogram',
            'symbol' => 'kg',
            'status' => 'active',
        ]);

        $category = MaterialCategory::query()->create([
            'code' => 'RAW',
            'name' => 'Raw',
            'status' => 'active',
        ]);

        $danaCategory = MaterialCategory::query()->create([
            'code' => 'DANA',
            'name' => 'Dana',
            'status' => 'active',
        ]);

        $this->rawWarehouse = Warehouse::query()->create(['code' => 'RAW', 'name' => 'Raw store', 'status' => 'active']);
        $this->danaWarehouse = Warehouse::query()->create(['code' => 'DANA', 'name' => 'Dana store', 'status' => 'active']);
        $wasteWarehouse = Warehouse::query()->create(['code' => 'WST', 'name' => 'Waste', 'status' => 'active']);

        $this->rawMaterial = Material::query()->create([
            'code' => 'PP',
            'name' => 'Polypropylene',
            'material_category_id' => $category->id,
            'unit_of_measurement_id' => $unit->id,
            'default_unit_cost' => 100,
            'inventory_bucket' => 'raw',
            'status' => 'active',
        ]);

        $this->danaMaterial = Material::query()->create([
            'code' => 'DANA-01',
            'name' => 'Red Dana',
            'material_category_id' => $danaCategory->id,
            'unit_of_measurement_id' => $unit->id,
            'default_unit_cost' => 0,
            'inventory_bucket' => 'dana',
            'status' => 'active',
        ]);

        $reusableWaste = Material::query()->create([
            'code' => 'REUSE',
            'name' => 'Reusable waste',
            'material_category_id' => $category->id,
            'unit_of_measurement_id' => $unit->id,
            'inventory_bucket' => 'other',
            'status' => 'active',
        ]);

        $this->hall = ProductionHall::query()->create(['code' => 'H1', 'name' => 'Hall 1', 'status' => 'active']);
        $machineType = MachineType::query()->create(['code' => 'EXT', 'name' => 'Extruder', 'status' => 'active']);
        $this->machine = Machine::query()->create([
            'code' => 'M1',
            'name' => 'Extruder 1',
            'machine_type_id' => $machineType->id,
            'production_hall_id' => $this->hall->id,
            'capacity' => 500,
            'status' => 'active',
        ]);

        $receipt = InventoryTransaction::query()->create([
            'transaction_number' => app(InventoryTransactionNumberGenerator::class)->next('purchase_receipt'),
            'type' => 'purchase_receipt',
            'status' => 'draft',
            'warehouse_id' => $this->rawWarehouse->id,
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        InventoryTransactionItem::query()->create([
            'inventory_transaction_id' => $receipt->id,
            'material_id' => $this->rawMaterial->id,
            'quantity' => 1000,
            'unit_cost' => 100,
            'total_value' => 100000,
        ]);

        app(InventoryPostingService::class)->post($receipt, $this->user);

        $this->wasteWarehouseId = $wasteWarehouse->id;
        $this->reusableWasteMaterialId = $reusableWaste->id;
    }

    private int $wasteWarehouseId;

    private int $reusableWasteMaterialId;

    public function test_full_posting_and_reversal_updates_inventory(): void
    {
        $batch = $this->createCompletedBatch();

        app(DanaBatchPostingService::class)->post($batch, $this->user);

        $batch->refresh();
        $this->assertSame('posted', $batch->status);
        $this->assertNotNull($batch->posted_at);
        $this->assertGreaterThan(0, (float) $batch->cost_per_kg);

        $rawBalance = InventoryBalance::query()
            ->where('warehouse_id', $this->rawWarehouse->id)
            ->where('material_id', $this->rawMaterial->id)
            ->first();

        $this->assertEqualsWithDelta(950.0, (float) $rawBalance->quantity, 0.0001);

        $danaBalance = InventoryBalance::query()
            ->where('warehouse_id', $this->danaWarehouse->id)
            ->where('material_id', $this->danaMaterial->id)
            ->first();

        $this->assertEqualsWithDelta(45.0, (float) $danaBalance->quantity, 0.0001);
        $this->assertEqualsWithDelta((float) $batch->cost_per_kg, (float) $danaBalance->average_unit_cost, 0.01);

        app(DanaBatchPostingService::class)->reverse($batch, $this->user);

        $batch->refresh();
        $this->assertSame('reversed', $batch->status);

        $rawBalance->refresh();
        $this->assertEqualsWithDelta(1000.0, (float) $rawBalance->quantity, 0.0001);
    }

    public function test_cancelled_batch_cannot_post(): void
    {
        $batch = $this->createDraftBatch();
        $workflow = app(DanaBatchWorkflowService::class);
        $workflow->transition($batch, 'submit', $this->user);
        $workflow->transition($batch->fresh(), 'cancel', $this->user);

        $this->expectException(DanaBatchException::class);
        app(DanaBatchPostingService::class)->post($batch->fresh(), $this->user);
    }

    public function test_duplicate_post_is_rejected(): void
    {
        $batch = $this->createCompletedBatch();
        $posting = app(DanaBatchPostingService::class);
        $posting->post($batch, $this->user);

        $this->expectException(DanaBatchException::class);
        $posting->post($batch->fresh(), $this->user);
    }

    public function test_dana_hub_is_accessible_for_production_operator(): void
    {
        $operator = User::factory()->create(['email_verified_at' => now()]);
        $operator->assignRole('production-operator');

        $this->actingAs($operator)
            ->get(route('erp.dana-production'))
            ->assertOk();
    }

    private function createDraftBatch(): DanaBatch
    {
        return app(DanaBatchPersistenceService::class)->create([
            'production_date' => now()->toDateString(),
            'machine_id' => $this->machine->id,
            'production_hall_id' => $this->hall->id,
            'raw_material_warehouse_id' => $this->rawWarehouse->id,
            'finished_dana_warehouse_id' => $this->danaWarehouse->id,
            'waste_warehouse_id' => $this->wasteWarehouseId,
            'output_material_id' => $this->danaMaterial->id,
            'output_kg' => 45,
            'reusable_waste_kg' => 3,
            'scrap_waste_kg' => 0,
            'process_loss_kg' => 2,
            'electricity_cost' => 50,
            'other_overhead_cost' => 25,
            'reusable_waste_material_id' => $this->reusableWasteMaterialId,
            'inputs' => [
                [
                    'material_id' => $this->rawMaterial->id,
                    'input_type' => 'raw',
                    'quantity_kg' => 50,
                ],
            ],
            'operators' => [
                ['operator_name' => 'Op A', 'labor_cost' => 200],
            ],
        ], $this->user);
    }

    private function createCompletedBatch(): DanaBatch
    {
        $batch = $this->createDraftBatch();
        $workflow = app(DanaBatchWorkflowService::class);
        $workflow->transition($batch, 'submit', $this->user);
        $workflow->transition($batch->fresh(), 'approve', $this->user);
        $workflow->transition($batch->fresh(), 'start', $this->user);
        $workflow->transition($batch->fresh(), 'complete', $this->user);

        return $batch->fresh();
    }
}