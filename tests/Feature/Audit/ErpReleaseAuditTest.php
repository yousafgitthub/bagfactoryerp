<?php

namespace Tests\Feature\Audit;

use App\Models\Attendance;
use App\Models\BagProduct;
use App\Models\BagProductVariant;
use App\Models\DanaBatch;
use App\Models\Employee;
use App\Models\GlJournalEntry;
use App\Models\InventoryBalance;
use App\Models\Machine;
use App\Models\MachineType;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\OvertimeRecord;
use App\Models\ProductionHall;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Bag\BagBatchPersistenceService;
use App\Services\Bag\BagBatchPostingService;
use App\Services\Bag\BagBatchWorkflowService;
use App\Services\Bag\BagOrderPersistenceService;
use App\Services\Bag\BagOrderWorkflowService;
use App\Services\Dana\DanaBatchPersistenceService;
use App\Services\Dana\DanaBatchPostingService;
use App\Services\Dana\DanaBatchWorkflowService;
use App\Services\Hr\AdvanceLoanService;
use App\Services\Hr\EmployeePersistenceService;
use App\Services\Hr\PayrollPaymentService;
use App\Services\Hr\PayrollRunService;
use App\Services\Inventory\InventoryReconciliationService;
use App\Services\Purchases\GoodsReceiptService;
use App\Services\Purchases\PurchaseInvoiceService;
use App\Services\Purchases\PurchaseOrderService;
use App\Services\Purchases\SupplierPersistenceService;
use App\Services\Sales\CustomerPersistenceService;
use App\Services\Sales\CustomerReceiptService;
use App\Services\Sales\SalesInvoiceService;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prompt 12 — cross-module release audit (SQLite in-memory).
 */
class ErpReleaseAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->owner = User::factory()->create(['email_verified_at' => now()]);
        $this->owner->assignRole('owner-admin');
    }

    public function test_supply_chain_purchase_through_customer_receipt(): void
    {
        $fixtures = $this->manufacturingFixtures();

        $supplier = app(SupplierPersistenceService::class)->create([
            'name' => 'Polymer Supplies',
            'opening_balance' => 0,
        ], $this->owner);

        $po = app(PurchaseOrderService::class)->createDraft([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $fixtures['rawWarehouse']->id,
            'order_date' => '2026-09-01',
        ], [[
            'material_id' => $fixtures['rawMaterial']->id,
            'unit_of_measurement_id' => $fixtures['rawMaterial']->unit_of_measurement_id,
            'ordered_qty' => 500,
            'unit_price' => 100,
            'discount_amount' => 0,
            'tax_rate' => 0,
        ]], $this->owner);
        $po = app(PurchaseOrderService::class)->confirm($po, $this->owner);

        $grn = app(GoodsReceiptService::class)->createDraft($po, '2026-09-02', [
            ['purchase_order_line_id' => $po->lines->first()->id, 'received_qty' => 500],
        ], $this->owner);
        app(GoodsReceiptService::class)->post($grn, $this->owner);

        $rawAfterGrn = (float) InventoryBalance::query()
            ->where('warehouse_id', $fixtures['rawWarehouse']->id)
            ->where('material_id', $fixtures['rawMaterial']->id)
            ->value('quantity');
        $this->assertEqualsWithDelta(500.0, $rawAfterGrn, 0.01);

        $invoice = app(PurchaseInvoiceService::class)->createFromPurchaseOrder($po->fresh('lines'), [
            'invoice_date' => '2026-09-03',
        ], [
            ['purchase_order_line_id' => $po->lines->first()->id, 'quantity' => 500],
        ], $this->owner);
        app(PurchaseInvoiceService::class)->post($invoice, $this->owner);

        $danaBatch = $this->postedDanaBatch($fixtures);
        $this->assertSame('posted', $danaBatch->status);
        $this->assertEqualsWithDelta(45.0, (float) InventoryBalance::query()
            ->where('warehouse_id', $fixtures['danaWarehouse']->id)
            ->where('material_id', $fixtures['danaMaterial']->id)
            ->value('quantity'), 0.01);

        $bagBatch = $this->postedBagBatch($fixtures, good: 200, danaKg: 1.6);
        $this->assertSame('posted', $bagBatch->status);

        $fgQty = (float) InventoryBalance::query()
            ->where('warehouse_id', $fixtures['fgWarehouse']->id)
            ->where('material_id', $fixtures['saleableMaterial']->id)
            ->value('quantity');
        $this->assertEqualsWithDelta(200.0, $fgQty, 0.01);

        $customer = app(CustomerPersistenceService::class)->create([
            'name' => 'Bag Retailer',
            'status' => 'active',
        ], $this->owner);

        $salesInvoice = app(SalesInvoiceService::class)->createDraft([
            'customer_id' => $customer->id,
            'warehouse_id' => $fixtures['fgWarehouse']->id,
            'invoice_date' => '2026-09-10',
            'sale_type' => 'credit',
        ], [[
            'material_id' => $fixtures['saleableMaterial']->id,
            'unit_of_measurement_id' => $fixtures['saleableMaterial']->unit_of_measurement_id,
            'quantity' => 50,
            'unit_price' => 25,
            'tax_rate' => 0,
        ]], $this->owner);
        app(SalesInvoiceService::class)->post($salesInvoice, $this->owner);

        $fgAfterSale = (float) InventoryBalance::query()
            ->where('warehouse_id', $fixtures['fgWarehouse']->id)
            ->where('material_id', $fixtures['saleableMaterial']->id)
            ->value('quantity');
        $this->assertEqualsWithDelta(150.0, $fgAfterSale, 0.01);

        $grand = (float) $salesInvoice->fresh()->grand_total;
        app(CustomerReceiptService::class)->record($customer->fresh(), [
            'receipt_date' => '2026-09-11',
            'amount' => $grand,
            'payment_method' => 'cash',
        ], [
            ['sales_invoice_id' => $salesInvoice->id, 'amount' => $grand],
        ], $this->owner);

        $this->assertSame('paid', $salesInvoice->fresh()->status);
        $this->assertSame([], app(InventoryReconciliationService::class)->compareBalancesToLedger());
        $this->assertAllJournalEntriesBalanced();
    }

    public function test_hr_attendance_payroll_and_payment_chain(): void
    {
        $department = \App\Models\Department::query()->create([
            'code' => 'PROD',
            'name' => 'Production',
            'status' => 'active',
        ]);
        \App\Models\Designation::query()->create([
            'code' => 'OP',
            'name' => 'Operator',
            'department_id' => $department->id,
            'status' => 'active',
        ]);

        $employee = app(EmployeePersistenceService::class)->create([
            'employee_code' => 'E2E-01',
            'name' => 'Line Operator',
            'joining_date' => '2026-01-01',
            'department_id' => $department->id,
            'salary_type' => 'monthly',
            'basic_monthly_salary' => 30000,
            'status' => 'active',
        ], $this->owner);

        Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-05',
            'status' => 'present',
        ]);
        Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => '2026-10-06',
            'status' => 'half_day',
        ]);

        OvertimeRecord::query()->create([
            'employee_id' => $employee->id,
            'overtime_date' => '2026-10-07',
            'hours' => 3,
            'rate_per_hour' => 150,
            'calculated_amount' => 450,
            'approval_status' => 'approved',
        ]);

        app(AdvanceLoanService::class)->recordAdvance($employee, [
            'advance_date' => '2026-10-01',
            'amount' => 3000,
            'per_payroll_deduction' => 1000,
        ], $this->owner);

        $payroll = app(PayrollRunService::class);
        $run = $payroll->createRun(Carbon::parse('2026-10-01'), Carbon::parse('2026-10-31'), $this->owner);
        $payroll->generate($run);
        $payroll->approve($run, $this->owner);

        /** @var Employee $employee */
        $employee = $employee->fresh();
        $record = $run->records()->where('employee_id', $employee->id)->first();
        $this->assertNotNull($record);
        $this->assertEqualsWithDelta(450.0, (float) $record->overtime_amount, 0.01);
        $advance = \App\Models\EmployeeAdvance::query()->where('employee_id', $employee->id)->first();
        $this->assertEqualsWithDelta(1000.0, (float) $advance->amount_repaid, 0.01);

        app(PayrollPaymentService::class)->recordPayment($record, [
            'payment_date' => '2026-11-01',
            'amount' => (float) $record->net_payable,
            'payment_method' => 'cash',
        ], $this->owner);

        $this->assertSame('paid', $record->fresh()->status);
        $this->assertAllJournalEntriesBalanced();

        $this->actingAs($this->owner)
            ->get(route('erp.reports.show', 'payroll-monthly'))
            ->assertOk();
    }

    public function test_guest_cannot_reach_erp_modules(): void
    {
        $this->get(route('erp.inventory'))->assertRedirect(route('login'));
        $this->get(route('erp.reports'))->assertRedirect(route('login'));
    }

    private function assertAllJournalEntriesBalanced(): void
    {
        GlJournalEntry::query()->each(function (GlJournalEntry $entry): void {
            $this->assertEquals(
                (string) $entry->total_debit,
                (string) $entry->total_credit,
                "Journal {$entry->id} ({$entry->source_type}) is unbalanced"
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function manufacturingFixtures(): array
    {
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

        $rawCat = MaterialCategory::query()->create(['code' => 'RAW', 'name' => 'Raw', 'status' => 'active']);
        $danaCat = MaterialCategory::query()->create(['code' => 'DANA', 'name' => 'Dana', 'status' => 'active']);
        $genCat = MaterialCategory::query()->create(['code' => 'GEN', 'name' => 'General', 'status' => 'active']);

        $rawWarehouse = Warehouse::query()->create(['code' => 'RAW-W', 'name' => 'Raw', 'status' => 'active']);
        $danaWarehouse = Warehouse::query()->create(['code' => 'DANA-W', 'name' => 'Dana', 'status' => 'active']);
        $fgWarehouse = Warehouse::query()->create(['code' => 'FG-W', 'name' => 'FG', 'status' => 'active']);
        $materialsWh = Warehouse::query()->create(['code' => 'MAT-W', 'name' => 'Materials', 'status' => 'active']);
        $rejectedWh = Warehouse::query()->create(['code' => 'REJ-W', 'name' => 'Rejected', 'status' => 'active']);
        $wasteWh = Warehouse::query()->create(['code' => 'WST-W', 'name' => 'Waste', 'status' => 'active']);

        $rawMaterial = Material::query()->create([
            'code' => 'PP-RAW',
            'name' => 'Polypropylene',
            'material_category_id' => $rawCat->id,
            'unit_of_measurement_id' => $unitKg->id,
            'default_unit_cost' => 100,
            'inventory_bucket' => 'raw',
            'status' => 'active',
        ]);

        $danaMaterial = Material::query()->create([
            'code' => 'DANA-R',
            'name' => 'Red Dana',
            'material_category_id' => $danaCat->id,
            'unit_of_measurement_id' => $unitKg->id,
            'inventory_bucket' => 'dana',
            'status' => 'active',
        ]);

        $saleableMaterial = Material::query()->create([
            'code' => 'BAG-G',
            'name' => 'Good bag',
            'material_category_id' => $genCat->id,
            'unit_of_measurement_id' => $unitPcs->id,
            'inventory_bucket' => 'finished_bag',
            'status' => 'active',
        ]);

        $rejectedMaterial = Material::query()->create([
            'code' => 'BAG-B',
            'name' => 'Rejected bag',
            'material_category_id' => $genCat->id,
            'unit_of_measurement_id' => $unitPcs->id,
            'inventory_bucket' => 'rejected_bag',
            'status' => 'active',
        ]);

        $reusableWaste = Material::query()->create([
            'code' => 'RWST',
            'name' => 'Reusable waste',
            'material_category_id' => $rawCat->id,
            'unit_of_measurement_id' => $unitKg->id,
            'inventory_bucket' => 'other',
            'status' => 'active',
        ]);

        $hall = ProductionHall::query()->create(['code' => 'H-A', 'name' => 'Hall A', 'status' => 'active']);
        $danaMachineType = MachineType::query()->create(['code' => 'EXT', 'name' => 'Extruder', 'status' => 'active']);
        $bagMachineType = MachineType::query()->create(['code' => 'BAG', 'name' => 'Bag line', 'status' => 'active']);
        $danaMachine = Machine::query()->create([
            'code' => 'EXT-1',
            'name' => 'Extruder 1',
            'machine_type_id' => $danaMachineType->id,
            'production_hall_id' => $hall->id,
            'status' => 'active',
        ]);
        $bagMachine = Machine::query()->create([
            'code' => 'BAG-1',
            'name' => 'Bag 1',
            'machine_type_id' => $bagMachineType->id,
            'production_hall_id' => $hall->id,
            'status' => 'active',
        ]);

        $product = BagProduct::query()->create([
            'code' => 'MED',
            'name' => 'Medium bag',
            'nominal_weight_per_bag_g' => 8,
            'unit_of_measurement_id' => $unitPcs->id,
            'status' => 'active',
        ]);
        $variant = BagProductVariant::query()->create([
            'bag_product_id' => $product->id,
            'variant_code' => 'RED',
            'name' => 'Red',
            'weight_per_bag_g' => 8,
            'saleable_material_id' => $saleableMaterial->id,
            'rejected_material_id' => $rejectedMaterial->id,
            'status' => 'active',
        ]);

        $order = app(BagOrderPersistenceService::class)->create([
            'bag_product_id' => $product->id,
            'bag_product_variant_id' => $variant->id,
            'planned_quantity' => 500,
            'finished_goods_warehouse_id' => $fgWarehouse->id,
            'dana_warehouse_id' => $danaWarehouse->id,
            'materials_warehouse_id' => $materialsWh->id,
            'rejected_warehouse_id' => $rejectedWh->id,
            'machine_id' => $bagMachine->id,
            'production_hall_id' => $hall->id,
        ], $this->owner);
        app(BagOrderWorkflowService::class)->transition($order, 'release', $this->owner);

        return compact(
            'rawWarehouse',
            'danaWarehouse',
            'fgWarehouse',
            'materialsWh',
            'rejectedWh',
            'wasteWh',
            'rawMaterial',
            'danaMaterial',
            'saleableMaterial',
            'rejectedMaterial',
            'reusableWaste',
            'hall',
            'danaMachine',
            'bagMachine',
            'order',
        );
    }

    private function postedDanaBatch(array $fixtures): DanaBatch
    {
        $batch = app(DanaBatchPersistenceService::class)->create([
            'production_date' => '2026-09-05',
            'machine_id' => $fixtures['danaMachine']->id,
            'production_hall_id' => $fixtures['hall']->id,
            'raw_material_warehouse_id' => $fixtures['rawWarehouse']->id,
            'finished_dana_warehouse_id' => $fixtures['danaWarehouse']->id,
            'waste_warehouse_id' => $fixtures['wasteWh']->id,
            'output_material_id' => $fixtures['danaMaterial']->id,
            'output_kg' => 45,
            'reusable_waste_kg' => 3,
            'scrap_waste_kg' => 0,
            'process_loss_kg' => 2,
            'electricity_cost' => 100,
            'other_overhead_cost' => 50,
            'reusable_waste_material_id' => $fixtures['reusableWaste']->id,
            'inputs' => [[
                'material_id' => $fixtures['rawMaterial']->id,
                'input_type' => 'raw',
                'quantity_kg' => 50,
            ]],
            'operators' => [['operator_name' => 'Op 1', 'labor_cost' => 500]],
        ], $this->owner);

        $workflow = app(DanaBatchWorkflowService::class);
        $workflow->transition($batch, 'submit', $this->owner);
        $workflow->transition($batch->fresh(), 'approve', $this->owner);
        $workflow->transition($batch->fresh(), 'start', $this->owner);
        $workflow->transition($batch->fresh(), 'complete', $this->owner);

        $batch = $batch->fresh();
        app(DanaBatchPostingService::class)->post($batch, $this->owner);

        return $batch->fresh();
    }

    private function postedBagBatch(array $fixtures, float $good, float $danaKg): \App\Models\BagProductionBatch
    {
        $batch = app(BagBatchPersistenceService::class)->create([
            'bag_production_order_id' => $fixtures['order']->id,
            'production_date' => '2026-09-08',
            'machine_id' => $fixtures['bagMachine']->id,
            'production_hall_id' => $fixtures['hall']->id,
            'good_quantity' => $good,
            'rejected_quantity' => 0,
            'dana_consumed_kg' => $danaKg,
            'actual_weight_per_good_bag_g' => 8,
            'process_loss_kg' => 0,
            'finished_goods_warehouse_id' => $fixtures['fgWarehouse']->id,
            'dana_warehouse_id' => $fixtures['danaWarehouse']->id,
            'materials_warehouse_id' => $fixtures['materialsWh']->id,
            'rejected_warehouse_id' => $fixtures['rejectedWh']->id,
        ], $this->owner);

        $workflow = app(BagBatchWorkflowService::class);
        $workflow->transition($batch, 'submit', $this->owner);
        $workflow->transition($batch->fresh(), 'approve', $this->owner);
        $workflow->transition($batch->fresh(), 'start', $this->owner);
        $workflow->transition($batch->fresh(), 'complete', $this->owner);

        $batch = $batch->fresh();
        app(BagBatchPostingService::class)->post($batch, $this->owner);

        return $batch->fresh();
    }
}