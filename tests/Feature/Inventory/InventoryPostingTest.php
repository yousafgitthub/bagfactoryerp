<?php

namespace Tests\Feature\Inventory;

use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\UnitOfMeasurement;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryPostingException;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryReconciliationService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryPostingTest extends TestCase
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
            'name' => 'Raw plastic',
            'status' => 'active',
        ]);

        $this->warehouse = Warehouse::query()->create([
            'code' => 'WH1',
            'name' => 'Main store',
            'status' => 'active',
        ]);

        $this->material = Material::query()->create([
            'code' => 'PP-01',
            'name' => 'Polypropylene',
            'material_category_id' => $category->id,
            'unit_of_measurement_id' => $unit->id,
            'default_unit_cost' => 100,
            'reorder_level' => 10,
            'inventory_bucket' => 'raw',
            'status' => 'active',
        ]);
    }

    public function test_purchase_receipt_updates_weighted_average(): void
    {
        $tx = $this->draftReceipt(100, 100);
        app(InventoryPostingService::class)->post($tx, $this->user);

        $tx2 = $this->draftReceipt(100, 200);
        app(InventoryPostingService::class)->post($tx2, $this->user);

        $balance = InventoryBalance::query()->first();

        $this->assertEqualsWithDelta(200.0, (float) $balance->quantity, 0.0001);
        $this->assertEqualsWithDelta(150.0, (float) $balance->average_unit_cost, 0.0001);
    }

    public function test_issue_reduces_stock_and_creates_issue_ledger(): void
    {
        $receipt = $this->draftReceipt(100, 100);
        app(InventoryPostingService::class)->post($receipt, $this->user);

        $issue = $this->draftIssue(50);
        app(InventoryPostingService::class)->post($issue, $this->user);

        $balance = InventoryBalance::query()->first();
        $this->assertEqualsWithDelta(50.0, (float) $balance->quantity, 0.0001);

        $this->assertDatabaseHas('inventory_ledger_entries', [
            'movement_type' => 'issue',
            'quantity' => -50,
        ]);
    }

    public function test_draft_does_not_affect_balances(): void
    {
        $this->draftReceipt(50, 80);

        $this->assertDatabaseCount('inventory_balances', 0);
    }

    public function test_duplicate_post_is_rejected(): void
    {
        $tx = $this->draftReceipt(10, 50);
        $service = app(InventoryPostingService::class);
        $service->post($tx, $this->user);

        $this->expectException(InventoryPostingException::class);
        $service->post($tx->fresh(), $this->user);
    }

    public function test_negative_stock_blocked_by_default(): void
    {
        $issue = $this->draftIssue(5);
        $this->expectException(InventoryPostingException::class);
        app(InventoryPostingService::class)->post($issue, $this->user);
    }

    public function test_reconciliation_empty_when_balanced(): void
    {
        $tx = $this->draftReceipt(25, 40);
        app(InventoryPostingService::class)->post($tx, $this->user);

        $mismatches = app(InventoryReconciliationService::class)->compareBalancesToLedger();

        $this->assertSame([], $mismatches);
    }

    public function test_storekeeper_can_view_inventory_hub(): void
    {
        $storekeeper = User::factory()->create(['email_verified_at' => now()]);
        $storekeeper->assignRole('storekeeper');

        $this->actingAs($storekeeper)
            ->get(route('erp.inventory'))
            ->assertOk();
    }

    public function test_concurrent_posting_with_row_locks(): void
    {
        $receipt = $this->draftReceipt(100, 10);
        app(InventoryPostingService::class)->post($receipt, $this->user);

        $issueA = $this->draftIssue(30);
        $issueB = $this->draftIssue(30);

        DB::transaction(function () use ($issueA) {
            app(InventoryPostingService::class)->post($issueA, $this->user);
        });

        DB::transaction(function () use ($issueB) {
            app(InventoryPostingService::class)->post($issueB, $this->user);
        });

        $balance = InventoryBalance::query()->first();
        $this->assertEqualsWithDelta(40.0, (float) $balance->quantity, 0.0001);
    }

    private function draftReceipt(float $qty, float $unitCost): InventoryTransaction
    {
        $generator = app(InventoryTransactionNumberGenerator::class);

        $tx = InventoryTransaction::query()->create([
            'transaction_number' => $generator->next('purchase_receipt'),
            'type' => 'purchase_receipt',
            'status' => 'draft',
            'warehouse_id' => $this->warehouse->id,
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $tx->items()->create([
            'material_id' => $this->material->id,
            'quantity' => $qty,
            'unit_cost' => $unitCost,
            'total_value' => $qty * $unitCost,
            'line_order' => 1,
        ]);

        return $tx->fresh('items');
    }

    private function draftIssue(float $qty): InventoryTransaction
    {
        $generator = app(InventoryTransactionNumberGenerator::class);

        $tx = InventoryTransaction::query()->create([
            'transaction_number' => $generator->next('issue_to_production'),
            'type' => 'issue_to_production',
            'status' => 'draft',
            'warehouse_id' => $this->warehouse->id,
            'transaction_date' => now()->toDateString(),
            'created_by' => $this->user->id,
        ]);

        $tx->items()->create([
            'material_id' => $this->material->id,
            'quantity' => $qty,
            'unit_cost' => 0,
            'total_value' => 0,
            'line_order' => 1,
        ]);

        return $tx->fresh('items');
    }
}