<?php

namespace Tests\Feature\Purchases;

use App\Models\InventoryBalance;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Purchases\GoodsReceiptService;
use App\Services\Purchases\PurchaseException;
use App\Services\Purchases\PurchaseInvoiceService;
use App\Services\Purchases\PurchaseOrderService;
use App\Services\Purchases\PurchaseReturnService;
use App\Services\Purchases\SupplierPaymentService;
use App\Services\Purchases\SupplierPersistenceService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Warehouse $warehouse;

    private Material $material;

  protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('owner-admin');

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

        $this->warehouse = Warehouse::query()->create([
            'code' => 'WH-P',
            'name' => 'Purchase WH',
            'status' => 'active',
        ]);

        $this->material = Material::query()->create([
            'code' => 'MAT-P1',
            'name' => 'Resin',
            'material_category_id' => $category->id,
            'unit_of_measurement_id' => $unit->id,
            'default_unit_cost' => 50,
            'reorder_level' => 5,
            'inventory_bucket' => 'raw',
            'status' => 'active',
        ]);
    }

    public function test_partial_grn_increases_stock_once(): void
    {
        $supplier = $this->supplier();
        $po = $this->confirmedPo($supplier, 100, 80);

        $grn = app(GoodsReceiptService::class)->createDraft($po, '2026-05-01', [
            ['purchase_order_line_id' => $po->lines->first()->id, 'received_qty' => 40],
        ], $this->user);

        app(GoodsReceiptService::class)->post($grn, $this->user);

        $balance = InventoryBalance::query()->first();
        $this->assertEqualsWithDelta(40.0, (float) $balance->quantity, 0.0001);

        $po->refresh();
        $this->assertSame('partially_received', $po->status);
        $this->assertEqualsWithDelta(40.0, (float) $po->lines->first()->received_qty, 0.0001);

        $this->expectException(PurchaseException::class);
        app(GoodsReceiptService::class)->post($grn->fresh(), $this->user);
    }

    public function test_invoice_matching_does_not_add_stock(): void
    {
        $supplier = $this->supplier();
        $po = $this->confirmedPo($supplier, 50, 10);

        $grn = app(GoodsReceiptService::class)->createDraft($po, '2026-05-02', [
            ['purchase_order_line_id' => $po->lines->first()->id, 'received_qty' => 30],
        ], $this->user);
        app(GoodsReceiptService::class)->post($grn, $this->user);

        $qtyBefore = (float) InventoryBalance::query()->value('quantity');

        $invoice = app(PurchaseInvoiceService::class)->createFromPurchaseOrder($po->fresh('lines'), [
            'invoice_date' => '2026-05-03',
        ], [
            ['purchase_order_line_id' => $po->lines->first()->id, 'quantity' => 30],
        ], $this->user);

        app(PurchaseInvoiceService::class)->post($invoice, $this->user);

        $qtyAfter = (float) InventoryBalance::query()->value('quantity');
        $this->assertEqualsWithDelta($qtyBefore, $qtyAfter, 0.0001);
        $this->assertSame('posted', $invoice->fresh()->status);
        $this->assertGreaterThan(0, (float) $supplier->fresh()->current_balance);
    }

    public function test_partial_payment_and_purchase_return(): void
    {
        $supplier = $this->supplier();
        $po = $this->confirmedPo($supplier, 20, 5);

        $grn = app(GoodsReceiptService::class)->createDraft($po, '2026-05-04', [
            ['purchase_order_line_id' => $po->lines->first()->id, 'received_qty' => 20],
        ], $this->user);
        app(GoodsReceiptService::class)->post($grn, $this->user);

        $invoice = app(PurchaseInvoiceService::class)->createFromPurchaseOrder($po->fresh('lines'), [
            'invoice_date' => '2026-05-05',
        ], [
            ['purchase_order_line_id' => $po->lines->first()->id, 'quantity' => 20],
        ], $this->user);
        app(PurchaseInvoiceService::class)->post($invoice, $this->user);

        $grand = (float) $invoice->fresh()->grand_total;

        app(SupplierPaymentService::class)->record($supplier->fresh(), [
            'payment_date' => '2026-05-06',
            'amount' => round($grand / 2, 2),
            'payment_method' => 'cash',
        ], [
            ['purchase_invoice_id' => $invoice->id, 'amount' => round($grand / 2, 2)],
        ], $this->user);

        $invoice->refresh();
        $this->assertSame('partially_paid', $invoice->status);

        $ret = app(PurchaseReturnService::class)->createDraft($supplier->fresh(), [
            'warehouse_id' => $this->warehouse->id,
            'return_date' => '2026-05-07',
        ], [
            [
                'material_id' => $this->material->id,
                'quantity' => 5,
                'unit_price' => 5,
                'unit_cost' => 5,
                'purchase_order_line_id' => $po->lines->first()->id,
            ],
        ], $this->user);

        app(PurchaseReturnService::class)->post($ret, $this->user);

        $balance = InventoryBalance::query()->first();
        $this->assertEqualsWithDelta(15.0, (float) $balance->quantity, 0.0001);
    }

    public function test_purchases_hub_route(): void
    {
        $response = $this->actingAs($this->user)->get(route('erp.purchases'));
        $response->assertOk();
    }

    private function supplier(): Supplier
    {
        return app(SupplierPersistenceService::class)->create([
            'name' => 'Acme Polymers',
            'opening_balance' => 0,
        ], $this->user);
    }

    private function confirmedPo(Supplier $supplier, float $qty, float $price)
    {
        $po = app(PurchaseOrderService::class)->createDraft([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $this->warehouse->id,
            'order_date' => '2026-05-01',
        ], [[
            'material_id' => $this->material->id,
            'unit_of_measurement_id' => $this->material->unit_of_measurement_id,
            'ordered_qty' => $qty,
            'unit_price' => $price,
            'discount_amount' => 0,
            'tax_rate' => 0,
        ]], $this->user);

        return app(PurchaseOrderService::class)->confirm($po, $this->user);
    }
}