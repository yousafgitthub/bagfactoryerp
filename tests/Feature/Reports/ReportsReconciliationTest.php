<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Services\Inventory\InventoryReconciliationService;
use App\Services\Reports\ReportFilters;
use App\Services\Reports\ReportRunner;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_reports_hub_requires_permission(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('production-operator');

        $this->actingAs($user)->get(route('erp.reports'))->assertForbidden();
    }

    public function test_accountant_can_open_inventory_valuation_report(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('accountant');

        $this->actingAs($user)
            ->get(route('erp.reports.show', 'inventory-valuation'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Reports/Show')->has('rows'));
    }

    public function test_inventory_valuation_matches_balance_sum(): void
    {
        $runner = app(ReportRunner::class);
        $result = $runner->run('inventory-valuation', new ReportFilters);

        $expected = (float) \Illuminate\Support\Facades\DB::table('inventory_balances')->sum('total_value');
        $reported = (float) ($result->summary['inventory_value'] ?? 0);

        $this->assertEqualsWithDelta($expected, $reported, 0.02);
    }

    public function test_reconciliation_service_has_no_drift_on_empty_db(): void
    {
        $rows = app(InventoryReconciliationService::class)->compareBalancesToLedger();

        $this->assertSame([], $rows);
    }

    public function test_csv_export_returns_stream(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('factory-manager');

        $response = $this->actingAs($user)
            ->get(route('erp.reports.export', ['reportKey' => 'production-yield-waste', 'format' => 'csv']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
    }
}