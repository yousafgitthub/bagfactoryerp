<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Accounting\FinancialReportService;
use App\Services\Dashboard\DashboardDataService;
use App\Services\Reports\ReportFilters;
use App\Services\Reports\SalesAnalyticsReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PerformanceQueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_month_dashboard_does_not_query_once_per_day(): void
    {
        $user = User::factory()->create();
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WH1',
            'name' => 'Main',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $customerId = DB::table('customers')->insertGetId([
            'code' => 'C1',
            'name' => 'Acme',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $invoiceDate = now()->startOfMonth()->addDays(10)->toDateString();
        DB::table('sales_invoices')->insert([
            'invoice_number' => 'SI-1',
            'customer_id' => $customerId,
            'warehouse_id' => $warehouseId,
            'invoice_date' => $invoiceDate,
            'status' => 'posted',
            'grand_total' => 100,
            'amount_paid' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $data = app(DashboardDataService::class)->forUser($user, ['period' => 'month']);
        $queries = DB::getQueryLog();

        $this->assertLessThan(80, count($queries));
        $outputQueries = collect($queries)->filter(
            fn (array $query) => str_contains($query['query'], 'output_kg')
        )->count();
        $this->assertLessThanOrEqual(6, $outputQueries);
        $this->assertCount(now()->daysInMonth, $data['production_trend']['points']);

        $chart = collect($data['monthly_sales_expenses'])->firstWhere('month', now()->format('M Y'));
        $this->assertEqualsWithDelta(100, $chart['sales'], 0.01);
        $card = collect($data['summary'])->firstWhere('key', 'month_sales');
        $this->assertEqualsWithDelta(100, $card['value'], 0.01);
    }

    public function test_financial_statements_load_accounts_in_bulk(): void
    {
        $now = now();
        $entryId = DB::table('gl_journal_entries')->insertGetId([
            'entry_number' => 'JE-1',
            'entry_date' => $now->toDateString(),
            'status' => 'posted',
            'total_debit' => 80,
            'total_credit' => 80,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $types = ['asset', 'liability', 'equity', 'revenue', 'expense'];
        foreach ($types as $index => $type) {
            $accountId = DB::table('gl_accounts')->insertGetId([
                'code' => 'T-'.$index,
                'name' => $type,
                'type' => $type,
                'normal_balance' => in_array($type, ['asset', 'expense'], true) ? 'debit' : 'credit',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('gl_journal_lines')->insert([
                'gl_journal_entry_id' => $entryId,
                'gl_account_id' => $accountId,
                'debit' => $type === 'asset' ? 80 : 0,
                'credit' => $type === 'asset' ? 0 : 20,
                'sort_order' => $index,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $trial = app(FinancialReportService::class)->trialBalance();
        $trialQueries = DB::getQueryLog();

        DB::flushQueryLog();
        $sheet = app(FinancialReportService::class)->balanceSheet();
        $sheetQueries = DB::getQueryLog();

        $this->assertCount(5, $trial);
        $this->assertSame(0, $this->pointLookups($trialQueries));
        $this->assertLessThanOrEqual(2, count($trialQueries));
        $this->assertSame(0, $this->pointLookups($sheetQueries));
        $this->assertLessThanOrEqual(2, count($sheetQueries));
        $this->assertArrayHasKey('retained_profit', $sheet);
    }

    public function test_outstanding_invoice_total_matches_per_invoice_balance(): void
    {
        $now = now();
        $warehouseId = DB::table('warehouses')->insertGetId([
            'code' => 'WH1', 'name' => 'Main', 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $customerId = DB::table('customers')->insertGetId([
            'code' => 'C1', 'name' => 'Acme', 'status' => 'active',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('sales_invoices')->insert([
            [
                'invoice_number' => 'SI-1', 'customer_id' => $customerId, 'warehouse_id' => $warehouseId,
                'invoice_date' => $now->toDateString(), 'status' => 'posted',
                'grand_total' => 100, 'amount_paid' => 40,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'invoice_number' => 'SI-2', 'customer_id' => $customerId, 'warehouse_id' => $warehouseId,
                'invoice_date' => $now->toDateString(), 'status' => 'posted',
                'grand_total' => 50, 'amount_paid' => 80,
                'created_at' => $now, 'updated_at' => $now,
            ],
            [
                'invoice_number' => 'SI-3', 'customer_id' => $customerId, 'warehouse_id' => $warehouseId,
                'invoice_date' => $now->toDateString(), 'status' => 'draft',
                'grand_total' => 999, 'amount_paid' => 0,
                'created_at' => $now, 'updated_at' => $now,
            ],
        ]);

        $report = app(SalesAnalyticsReportService::class)->outstanding(new ReportFilters);

        $this->assertEqualsWithDelta(60, $report->summary['open_invoice_total'], 0.01);
    }

    /**
     * @param  list<array{query: string}>  $queries
     */
    private function pointLookups(array $queries): int
    {
        return collect($queries)->filter(
            fn (array $query) => str_contains($query['query'], 'gl_accounts') && str_contains($query['query'], 'limit 1')
        )->count();
    }
}
