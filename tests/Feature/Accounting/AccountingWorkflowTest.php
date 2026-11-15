<?php

namespace Tests\Feature\Accounting;

use App\Models\ExpenseCategory;
use App\Models\GlJournalEntry;
use App\Models\GlJournalLine;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\Supplier;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\AccountingException;
use App\Services\Accounting\ExpenseService;
use App\Services\Accounting\JournalEntryService;
use App\Services\Purchases\GoodsReceiptService;
use App\Services\Purchases\PurchaseInvoiceService;
use App\Services\Purchases\PurchaseOrderService;
use App\Services\Purchases\SupplierPersistenceService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->user->assignRole('owner-admin');
    }

    public function test_expense_posts_balanced_journal(): void
    {
        $category = ExpenseCategory::query()->create([
            'code' => 'ELEC',
            'name' => 'Electricity',
            'status' => 'active',
        ]);

        $expense = app(ExpenseService::class)->create([
            'expense_category_id' => $category->id,
            'expense_date' => '2026-06-01',
            'amount' => 1500,
            'payment_status' => 'paid',
            'payment_method' => 'cash',
        ], $this->user);

        $entry = GlJournalEntry::query()->findOrFail($expense->gl_journal_entry_id);
        $this->assertEquals($entry->total_debit, $entry->total_credit);

        $lines = GlJournalLine::query()->where('gl_journal_entry_id', $entry->id)->get();
        $this->assertGreaterThanOrEqual(2, $lines->count());
    }

    public function test_purchase_grn_and_invoice_create_balanced_gl_entries(): void
    {
        [$supplier, $material, $warehouse] = $this->purchaseFixtures();

        $po = app(PurchaseOrderService::class)->createDraft([
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'order_date' => '2026-06-02',
            'expected_date' => null,
            'notes' => null,
        ], [
            [
                'material_id' => $material->id,
                'unit_of_measurement_id' => $material->unit_of_measurement_id,
                'ordered_qty' => 10,
                'unit_price' => 100,
                'discount_amount' => 0,
                'tax_rate' => 0,
            ],
        ], $this->user);

        $po = app(PurchaseOrderService::class)->confirm($po, $this->user);

        $grn = app(GoodsReceiptService::class)->createDraft($po, '2026-06-03', [
            ['purchase_order_line_id' => $po->lines->first()->id, 'received_qty' => 10],
        ], $this->user);

        app(GoodsReceiptService::class)->post($grn, $this->user);

        $grnEntry = GlJournalEntry::query()->where('source_type', 'goods_receipt')->where('source_id', $grn->id)->firstOrFail();
        $this->assertEquals($grnEntry->total_debit, $grnEntry->total_credit);

        $invoice = app(PurchaseInvoiceService::class)->createFromPurchaseOrder($po->fresh('lines'), [
            'invoice_date' => '2026-06-04',
        ], [
            ['purchase_order_line_id' => $po->lines->first()->id, 'quantity' => 10],
        ], $this->user);
        app(PurchaseInvoiceService::class)->post($invoice, $this->user);

        $piEntry = GlJournalEntry::query()->where('source_type', 'purchase_invoice')->where('source_id', $invoice->id)->firstOrFail();
        $this->assertEquals($piEntry->total_debit, $piEntry->total_credit);
    }

    public function test_journal_service_rejects_unbalanced_entry(): void
    {
        $this->expectException(AccountingException::class);

        app(JournalEntryService::class)->postBalanced(
            entryDate: '2026-06-05',
            sourceType: 'manual_test',
            sourceId: 1,
            description: 'Bad',
            lines: [
                ['account_code' => '1000', 'debit' => 100],
                ['account_code' => '4000', 'credit' => 50],
            ],
            actor: $this->user,
        );
    }

    public function test_duplicate_source_journal_is_idempotent(): void
    {
        $service = app(JournalEntryService::class);

        $first = $service->postBalanced(
            entryDate: '2026-06-06',
            sourceType: 'manual_test',
            sourceId: 99,
            description: 'Once',
            lines: [
                ['account_code' => '1000', 'debit' => 10],
                ['account_code' => '4000', 'credit' => 10],
            ],
            actor: $this->user,
        );

        $second = $service->postBalanced(
            entryDate: '2026-06-06',
            sourceType: 'manual_test',
            sourceId: 99,
            description: 'Once',
            lines: [
                ['account_code' => '1000', 'debit' => 10],
                ['account_code' => '4000', 'credit' => 10],
            ],
            actor: $this->user,
        );

        $this->assertSame($first->id, $second->id);
        $this->assertEquals(1, GlJournalEntry::query()->where('source_type', 'manual_test')->where('source_id', 99)->count());
    }

    /**
     * @return array{Supplier, Material, Warehouse}
     */
    private function purchaseFixtures(): array
    {
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

        $warehouse = Warehouse::query()->create([
            'code' => 'WH-A',
            'name' => 'WH',
            'status' => 'active',
        ]);

        $material = Material::query()->create([
            'code' => 'MAT-A',
            'name' => 'Resin',
            'material_category_id' => $category->id,
            'unit_of_measurement_id' => $unit->id,
            'default_unit_cost' => 50,
            'reorder_level' => 5,
            'inventory_bucket' => 'raw',
            'status' => 'active',
        ]);

        $supplier = app(SupplierPersistenceService::class)->create([
            'name' => 'Supplier A',
            'opening_balance' => 0,
        ], $this->user);

        return [$supplier, $material, $warehouse];
    }
}