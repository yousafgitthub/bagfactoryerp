<?php

namespace Tests\Feature\Bag;

use App\Models\BagProduct;
use App\Models\BagProductVariant;
use App\Models\BagProductionBatch;
use App\Models\BagProductionOrder;
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
use App\Services\Bag\BagBatchException;
use App\Services\Bag\BagBatchPersistenceService;
use App\Services\Bag\BagBatchPostingService;
use App\Services\Bag\BagBatchWorkflowService;
use App\Services\Bag\BagOrderPersistenceService;
use App\Services\Bag\BagOrderWorkflowService;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BagBatchWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Warehouse $danaWarehouse;

    private Warehouse $fgWarehouse;

    private Material $danaMaterial;

    private Material $saleableMaterial;

    private Material $rejectedMaterial;

    private BagProductionOrder $order;

    private Machine $machine;

    private ProductionHall $hall;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('production-manager');

        $unitKg = UnitOfMeasurement::query()->create([
            'code' => 'KG',
            'name' => 'Kilogram',
            'symbol' => 'kg',
            'status' => 'active',
        ]);

        $unitPcs = UnitOfMeasurement::query()->create([
            'code' => 'PCS',
            'name' => 'Pieces',
            'symbol' => 'pcs',
            'status' => 'active',
        ]);

        $cat = MaterialCategory::query()->create(['code' => 'GEN', 'name' => 'General', 'status' => 'active']);

        $this->danaWarehouse = Warehouse::query()->create(['code' => 'DANA', 'name' => 'Dana', 'status' => 'active']);
        $this->fgWarehouse = Warehouse::query()->create(['code' => 'FG', 'name' => 'Finished', 'status' => 'active']);
        $materialsWh = Warehouse::query()->create(['code' => 'MAT', 'name' => 'Materials', 'status' => 'active']);
        $rejectedWh = Warehouse::query()->create(['code' => 'REJ', 'name' => 'Rejected', 'status' => 'active']);

        $this->danaMaterial = Material::query()->create([
            'code' => 'DANA-R',
            'name' => 'Red Dana',
            'material_category_id' => $cat->id,
            'unit_of_measurement_id' => $unitKg->id,
            'inventory_bucket' => 'dana',
            'status' => 'active',
        ]);

        $this->saleableMaterial = Material::query()->create([
            'code' => 'BAG-SALE',
            'name' => 'Medium red bag',
            'material_category_id' => $cat->id,
            'unit_of_measurement_id' => $unitPcs->id,
            'inventory_bucket' => 'finished_bag',
            'status' => 'active',
        ]);

        $this->rejectedMaterial = Material::query()->create([
            'code' => 'BAG-REJ',
            'name' => 'Rejected medium bag',
            'material_category_id' => $cat->id,
            'unit_of_measurement_id' => $unitPcs->id,
            'inventory_bucket' => 'rejected_bag',
            'status' => 'active',
        ]);

        $product = BagProduct::query()->create([
            'code' => 'MED-RED',
            'name' => 'Medium red T-shirt bag',
            'nominal_weight_per_bag_g' => 8,
            'unit_of_measurement_id' => $unitPcs->id,
            'status' => 'active',
        ]);

        $variant = BagProductVariant::query()->create([
            'bag_product_id' => $product->id,
            'variant_code' => 'RED',
            'name' => 'Red',
            'weight_per_bag_g' => 8,
            'saleable_material_id' => $this->saleableMaterial->id,
            'rejected_material_id' => $this->rejectedMaterial->id,
            'status' => 'active',
        ]);

        $this->hall = ProductionHall::query()->create(['code' => 'H1', 'name' => 'Hall 1', 'status' => 'active']);
        $machineType = MachineType::query()->create(['code' => 'BAG', 'name' => 'Bag machine', 'status' => 'active']);
        $this->machine = Machine::query()->create([
            'code' => 'BM1',
            'name' => 'Bag line 1',
            'machine_type_id' => $machineType->id,
            'production_hall_id' => $this->hall->id,
            'status' => 'active',
        ]);

        $danaReceipt = InventoryTransaction::query()->create([
            'transaction_number' => app(InventoryTransactionNumberGenerator::class)->next('purchase_receipt'),
            'type' => 'purchase_receipt',
            'status' => 'draft',
            'warehouse_id' => $this->danaWarehouse->id,
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        InventoryTransactionItem::query()->create([
            'inventory_transaction_id' => $danaReceipt->id,
            'material_id' => $this->danaMaterial->id,
            'quantity' => 500,
            'unit_cost' => 200,
            'total_value' => 100000,
        ]);

        app(InventoryPostingService::class)->post($danaReceipt, $this->user);

        $orders = app(BagOrderPersistenceService::class);
        $this->order = $orders->create([
            'bag_product_id' => $product->id,
            'bag_product_variant_id' => $variant->id,
            'planned_quantity' => 5000,
            'finished_goods_warehouse_id' => $this->fgWarehouse->id,
            'dana_warehouse_id' => $this->danaWarehouse->id,
            'materials_warehouse_id' => $materialsWh->id,
            'rejected_warehouse_id' => $rejectedWh->id,
            'machine_id' => $this->machine->id,
            'production_hall_id' => $this->hall->id,
        ], $this->user);

        $workflow = app(BagOrderWorkflowService::class);
        $workflow->transition($this->order, 'release', $this->user);
        $this->order->refresh();
    }

    public function test_posting_and_partial_batch_updates_stock(): void
    {
        $batch = $this->createCompletedBatch(good: 1000, rejected: 50, danaKg: 8.4, weightG: 8);

        app(BagBatchPostingService::class)->post($batch, $this->user);

        $batch->refresh();
        $this->assertSame('posted', $batch->status);
        $this->assertGreaterThan(0, (float) $batch->cost_per_good_bag);

        $danaBalance = InventoryBalance::query()
            ->where('warehouse_id', $this->danaWarehouse->id)
            ->where('material_id', $this->danaMaterial->id)
            ->first();

        $this->assertEqualsWithDelta(491.6, (float) $danaBalance->quantity, 0.01);

        $fgBalance = InventoryBalance::query()
            ->where('warehouse_id', $this->fgWarehouse->id)
            ->where('material_id', $this->saleableMaterial->id)
            ->first();

        $this->assertEqualsWithDelta(1000.0, (float) $fgBalance->quantity, 0.01);

        $rejBalance = InventoryBalance::query()
            ->where('warehouse_id', $this->order->rejected_warehouse_id)
            ->where('material_id', $this->rejectedMaterial->id)
            ->first();

        $this->assertEqualsWithDelta(50.0, (float) $rejBalance->quantity, 0.01);

        $this->order->refresh();
        $this->assertEqualsWithDelta(1000.0, (float) $this->order->produced_quantity, 0.01);
        $this->assertEqualsWithDelta(50.0, (float) $this->order->rejected_quantity, 0.01);
    }

    public function test_reversal_restores_dana_stock(): void
    {
        $batch = $this->createCompletedBatch(good: 500, rejected: 0, danaKg: 4, weightG: 8);
        $posting = app(BagBatchPostingService::class);
        $posting->post($batch, $this->user);

        $posting->reverse($batch->fresh(), $this->user);

        $danaBalance = InventoryBalance::query()
            ->where('warehouse_id', $this->danaWarehouse->id)
            ->where('material_id', $this->danaMaterial->id)
            ->first();

        $this->assertEqualsWithDelta(500.0, (float) $danaBalance->quantity, 0.01);
    }

    public function test_cancelled_batch_cannot_post(): void
    {
        $batch = $this->createDraftBatch();
        $workflow = app(BagBatchWorkflowService::class);
        $workflow->transition($batch, 'submit', $this->user);
        $workflow->transition($batch->fresh(), 'cancel', $this->user);

        $this->expectException(BagBatchException::class);
        app(BagBatchPostingService::class)->post($batch->fresh(), $this->user);
    }

    public function test_draft_batch_does_not_deduct_stock(): void
    {
        $batch = $this->createDraftBatch();
        $danaBefore = (float) InventoryBalance::query()
            ->where('warehouse_id', $this->danaWarehouse->id)
            ->where('material_id', $this->danaMaterial->id)
            ->value('quantity');

        $this->assertEqualsWithDelta(500.0, $danaBefore, 0.01);
        $this->assertSame('draft', $batch->status);
    }

    public function test_bag_hub_accessible_for_production_operator(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('production-operator');

        $this->actingAs($operator)
            ->get(route('erp.shopping-bag-production'))
            ->assertOk();
    }

    private function createDraftBatch(): BagProductionBatch
    {
        return app(BagBatchPersistenceService::class)->create([
            'bag_production_order_id' => $this->order->id,
            'production_date' => now()->toDateString(),
            'machine_id' => $this->machine->id,
            'production_hall_id' => $this->hall->id,
            'good_quantity' => 100,
            'rejected_quantity' => 0,
            'dana_consumed_kg' => 0.8,
            'actual_weight_per_good_bag_g' => 8,
            'process_loss_kg' => 0,
            'finished_goods_warehouse_id' => $this->fgWarehouse->id,
            'dana_warehouse_id' => $this->danaWarehouse->id,
            'materials_warehouse_id' => $this->order->materials_warehouse_id,
            'rejected_warehouse_id' => $this->order->rejected_warehouse_id,
        ], $this->user);
    }

    private function createCompletedBatch(float $good, float $rejected, float $danaKg, float $weightG): BagProductionBatch
    {
        $batch = app(BagBatchPersistenceService::class)->create([
            'bag_production_order_id' => $this->order->id,
            'production_date' => now()->toDateString(),
            'machine_id' => $this->machine->id,
            'production_hall_id' => $this->hall->id,
            'good_quantity' => $good,
            'rejected_quantity' => $rejected,
            'dana_consumed_kg' => $danaKg,
            'actual_weight_per_good_bag_g' => $weightG,
            'process_loss_kg' => 0,
            'finished_goods_warehouse_id' => $this->fgWarehouse->id,
            'dana_warehouse_id' => $this->danaWarehouse->id,
            'materials_warehouse_id' => $this->order->materials_warehouse_id,
            'rejected_warehouse_id' => $this->order->rejected_warehouse_id,
        ], $this->user);

        $workflow = app(BagBatchWorkflowService::class);
        $workflow->transition($batch, 'submit', $this->user);
        $workflow->transition($batch->fresh(), 'approve', $this->user);
        $workflow->transition($batch->fresh(), 'start', $this->user);
        $workflow->transition($batch->fresh(), 'complete', $this->user);

        return $batch->fresh();
    }
}