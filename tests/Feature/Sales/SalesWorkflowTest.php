<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Sales\CustomerPersistenceService;
use App\Services\Sales\CustomerReceiptService;
use App\Services\Sales\SalesException;
use App\Services\Sales\SalesInvoiceService;
use App\Services\Sales\SalesReturnService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Warehouse $warehouse;

    private Material $fgMaterial;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('owner-admin');

        $unit = UnitOfMeasurement::query()->create([
            'code' => 'EA',
            'name' => 'Each',
            'symbol' => 'ea',
            'status' => 'active',
        ]);

        $category = MaterialCategory::query()->create([
            'code' => 'FG',
            'name' => 'Finished',
            'status' => 'active',
        ]);

        $this->warehouse = Warehouse::query()->create([
            'code' => 'WH-FG',
            'name' => 'FG WH',
            'status' => 'active',
        ]);

        $this->fgMaterial = Material::query()->create([
            'code' => 'FG-BAG',
            'name' => 'Saleable bags',
            'material_category_id' => $category->id,
            'unit_of_measurement_id' => $unit->id,
            'default_unit_cost' => 0,
            'reorder_level' => 0,
            'inventory_bucket' => 'finished_bag',
            'status' => 'active',
        ]);

        $this->seedFgStock(100, 12.5);
    }

    private function seedFgStock(float $qty, float $unitCost): void
    {
        $tx = InventoryTransaction::query()->create([
            'transaction_number' => 'OS-TEST-1',
            'type' => 'opening_stock',
            'status' => 'draft',
            'warehouse_id' => $this->warehouse->id,
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        InventoryTransactionItem::query()->create([
            'inventory_transaction_id' => $tx->id,
            'material_id' => $this->fgMaterial->id,
            'quantity' => $qty,
            'unit_cost' => $unitCost,
        ]);

        app(InventoryPostingService::class)->post($tx, $this->user);
    }

    private function customer(): Customer
    {
        return app(CustomerPersistenceService::class)->create([
            'name' => 'Retail Co',
            'status' => 'active',
        ], $this->user);
    }

    public function test_posted_invoice_deducts_stock_and_records_wac_cogs(): void
    {
        $customer = $this->customer();

        $invoice = app(SalesInvoiceService::class)->createDraft([
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-06-01',
            'sale_type' => 'credit',
        ], [
            [
                'material_id' => $this->fgMaterial->id,
                'unit_of_measurement_id' => $this->fgMaterial->unit_of_measurement_id,
                'quantity' => 10,
                'unit_price' => 20,
                'tax_rate' => 0,
            ],
        ], $this->user);

        app(SalesInvoiceService::class)->post($invoice, $this->user);

        $balance = InventoryBalance::query()->first();
        $this->assertEqualsWithDelta(90.0, (float) $balance->quantity, 0.0001);

        $invoice->refresh();
        $this->assertEqualsWithDelta(125.0, (float) $invoice->cogs_total, 0.01);
        $this->assertGreaterThan(0, (float) $customer->fresh()->current_balance);
    }

    public function test_credit_sale_partial_payment_and_oversell_guard(): void
    {
        $customer = $this->customer();

        $invoice = app(SalesInvoiceService::class)->createDraft([
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-06-02',
            'sale_type' => 'credit',
        ], [
            [
                'material_id' => $this->fgMaterial->id,
                'unit_of_measurement_id' => $this->fgMaterial->unit_of_measurement_id,
                'quantity' => 5,
                'unit_price' => 30,
                'tax_rate' => 10,
            ],
        ], $this->user);

        app(SalesInvoiceService::class)->post($invoice, $this->user);
        $grand = (float) $invoice->fresh()->grand_total;

        app(CustomerReceiptService::class)->record($customer->fresh(), [
            'receipt_date' => '2026-06-03',
            'amount' => round($grand / 2, 2),
            'payment_method' => 'cash',
        ], [
            ['sales_invoice_id' => $invoice->id, 'amount' => round($grand / 2, 2)],
        ], $this->user);

        $this->assertSame('partially_paid', $invoice->fresh()->status);

        $huge = app(SalesInvoiceService::class)->createDraft([
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-06-04',
            'sale_type' => 'credit',
        ], [
            [
                'material_id' => $this->fgMaterial->id,
                'unit_of_measurement_id' => $this->fgMaterial->unit_of_measurement_id,
                'quantity' => 500,
                'unit_price' => 1,
                'tax_rate' => 0,
            ],
        ], $this->user);

        $this->expectException(SalesException::class);
        app(SalesInvoiceService::class)->post($huge, $this->user);
    }

    public function test_saleable_return_increases_stock(): void
    {
        $customer = $this->customer();

        $invoice = app(SalesInvoiceService::class)->createDraft([
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-06-05',
            'sale_type' => 'cash',
        ], [
            [
                'material_id' => $this->fgMaterial->id,
                'unit_of_measurement_id' => $this->fgMaterial->unit_of_measurement_id,
                'quantity' => 8,
                'unit_price' => 15,
                'tax_rate' => 0,
            ],
        ], $this->user);

        app(SalesInvoiceService::class)->post($invoice, $this->user);
        $qtyAfterSale = (float) InventoryBalance::query()->value('quantity');

        $ret = app(SalesReturnService::class)->createDraft([
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'return_date' => '2026-06-06',
        ], [
            [
                'material_id' => $this->fgMaterial->id,
                'quantity' => 3,
                'condition' => 'saleable',
                'unit_price' => 15,
                'tax_rate' => 0,
            ],
        ], $this->user);

        app(SalesReturnService::class)->post($ret, $this->user);

        $qtyAfterReturn = (float) InventoryBalance::query()->value('quantity');
        $this->assertEqualsWithDelta($qtyAfterSale + 3, $qtyAfterReturn, 0.0001);
        $this->assertSame('paid', $invoice->fresh()->status);
    }
}