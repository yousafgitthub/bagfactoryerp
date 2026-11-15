<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use App\Services\Dashboard\DashboardDataService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_dashboard_payload_includes_database_backed_summary(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $user->assignRole('owner-admin');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('dashboard.summary', 12)
            ->has('dashboard.production_trend.points')
            ->where('dashboard.summary.6.key', 'active_employees'));
    }

    public function test_dashboard_service_returns_structure_without_transactional_tables(): void
    {
        $user = User::factory()->create();

        $data = app(DashboardDataService::class)->forUser($user);

        $this->assertArrayHasKey('summary', $data);
        $this->assertCount(12, $data['summary']);
        $this->assertArrayHasKey('points', $data['production_trend']);
        $this->assertSame([], $data['inventory_alerts']);
        $this->assertSame([], $data['recent_transactions']);
        $this->assertArrayHasKey('filters', $data);
    }

    public function test_dashboard_reads_attendance_expenses_and_posted_journals(): void
    {
        $category = ExpenseCategory::query()->create([
            'code' => 'ELEC',
            'name' => 'Electricity',
            'status' => 'active',
        ]);
        Expense::query()->create([
            'expense_number' => 'EXP-1',
            'expense_category_id' => $category->id,
            'expense_date' => now()->startOfMonth()->toDateString(),
            'amount' => 250,
            'payment_status' => 'paid',
        ]);

        $employee = Employee::query()->create([
            'employee_code' => 'E1',
            'name' => 'Operator',
            'joining_date' => now()->toDateString(),
            'salary_type' => 'monthly',
            'status' => 'active',
            'is_active' => true,
        ]);
        Attendance::query()->create([
            'employee_id' => $employee->id,
            'attendance_date' => now()->toDateString(),
            'status' => 'present',
        ]);

        $cashId = DB::table('gl_accounts')->where('code', '1000')->value('id');
        $entryId = DB::table('gl_journal_entries')->insertGetId([
            'entry_number' => 'JE-DASH',
            'entry_date' => now()->toDateString(),
            'status' => 'posted',
            'total_debit' => 75,
            'total_credit' => 75,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('gl_journal_lines')->insert([
            'gl_journal_entry_id' => $entryId,
            'gl_account_id' => $cashId,
            'debit' => 75,
            'credit' => 0,
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = app(DashboardDataService::class)->forUser(User::factory()->create());
        $cards = collect($data['summary']);

        $this->assertEqualsWithDelta(250, $cards->firstWhere('key', 'month_operating_expenses')['value'], 0.01);
        $this->assertSame(1, $cards->firstWhere('key', 'today_attendance')['value']);
        $this->assertEqualsWithDelta(75, $data['financial_overview']['cash_on_hand'], 0.01);
    }

    public function test_dashboard_respects_period_filter(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('owner-admin');

        $response = $this->actingAs($user)->get(route('dashboard', ['period' => 'today']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('dashboard.filters.period', 'today'));
    }
}