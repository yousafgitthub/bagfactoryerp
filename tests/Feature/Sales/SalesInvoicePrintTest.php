<?php

namespace Tests\Feature\Sales;

use App\Models\Customer;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\SalesInvoice;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Sales\CustomerPersistenceService;
use App\Services\Sales\CustomerReceiptService;
use App\Services\Sales\SalesInvoiceService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesInvoicePrintTest extends TestCase
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

        $this->seedFgStock(200, 10);
    }

    private function seedFgStock(float $qty, float $unitCost): void
    {
        $tx = InventoryTransaction::query()->create([
            'transaction_number' => 'OS-PRINT-1',
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
            'name' => 'Print Test Customer',
            'status' => 'active',
        ], $this->user);
    }

    private function demoInvoice(): SalesInvoice
    {
        $customer = $this->customer();

        $invoice = app(SalesInvoiceService::class)->createDraft([
            'customer_id' => $customer->id,
            'warehouse_id' => $this->warehouse->id,
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-15',
            'sale_type' => 'credit',
            'notes' => 'Payment due within 14 days.',
        ], [
            [
                'material_id' => $this->fgMaterial->id,
                'unit_of_measurement_id' => $this->fgMaterial->unit_of_measurement_id,
                'quantity' => 4,
                'unit_price' => 25,
                'discount_amount' => 2,
                'tax_rate' => 10,
            ],
            [
                'material_id' => $this->fgMaterial->id,
                'unit_of_measurement_id' => $this->fgMaterial->unit_of_measurement_id,
                'quantity' => 2,
                'unit_price' => 50,
                'discount_amount' => 0,
                'tax_rate' => 5,
            ],
        ], $this->user);

        app(SalesInvoiceService::class)->post($invoice, $this->user);

        return $invoice->fresh(['lines']);
    }

    public function test_authorized_user_can_open_print_view_with_matching_totals(): void
    {
        $invoice = $this->demoInvoice();

        $response = $this->actingAs($this->user)
            ->get(route('sales.invoices.print', $invoice));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Sales/Invoices/Print')
            ->where('invoice.invoice_number', $invoice->invoice_number)
            ->where('invoice.grand_total', $invoice->grand_total)
            ->where('invoice.subtotal', $invoice->subtotal)
            ->where('invoice.tax_total', $invoice->tax_total)
            ->where('outstanding', $invoice->outstandingAmount())
            ->has('invoice.lines', 2)
        );
    }

    public function test_guest_is_denied_print_route(): void
    {
        $invoice = $this->demoInvoice();

        $this->get(route('sales.invoices.print', $invoice))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_sales_access_is_forbidden(): void
    {
        $invoice = $this->demoInvoice();

        $restricted = User::factory()->create(['email_verified_at' => now()]);
        $restricted->assignRole('production-operator');

        $this->actingAs($restricted)
            ->get(route('sales.invoices.print', $invoice))
            ->assertForbidden();
    }

    public function test_partially_paid_invoice_shows_paid_and_outstanding_on_print(): void
    {
        $invoice = $this->demoInvoice();
        $customer = $invoice->customer;
        $grand = (float) $invoice->grand_total;
        $partial = round($grand / 2, 2);

        app(CustomerReceiptService::class)->record($customer, [
            'receipt_date' => '2026-07-02',
            'amount' => $partial,
            'payment_method' => 'cash',
        ], [
            ['sales_invoice_id' => $invoice->id, 'amount' => $partial],
        ], $this->user);

        $invoice->refresh();

        $this->actingAs($this->user)
            ->get(route('sales.invoices.print', $invoice))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('invoice.amount_paid', (string) $invoice->amount_paid)
                ->where('outstanding', $invoice->outstandingAmount())
                ->where('statusLabel', 'Partially paid')
            );
    }
}