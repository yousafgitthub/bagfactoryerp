<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpUxWave2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_employee_hub_includes_breadcrumb_trail(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('hr-manager');

        $this->actingAs($user)
            ->get(route('erp.employees'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Hr/Employees/Hub')
                ->has('breadcrumbs', 2)
                ->where('breadcrumbs.0.label', 'Dashboard')
                ->where('breadcrumbs.1.label', 'Employees'));
    }

    public function test_trial_balance_includes_accounting_breadcrumbs(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('owner-admin');

        $this->actingAs($user)
            ->get(route('accounting.reports.trial-balance'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Accounting/Reports/TrialBalance')
                ->has('breadcrumbs', 3)
                ->where('breadcrumbs.1.label', 'Accounting')
                ->where('breadcrumbs.2.label', 'Trial balance'));
    }

    public function test_catalog_report_show_includes_reports_breadcrumbs(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('owner-admin');

        $this->actingAs($user)
            ->get(route('erp.reports.show', 'inventory-valuation'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Show')
                ->has('breadcrumbs', 3)
                ->where('breadcrumbs.1.label', 'Reports'));
    }
}