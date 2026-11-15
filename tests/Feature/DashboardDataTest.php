<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Dashboard\DashboardDataService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_dashboard_respects_period_filter(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('owner-admin');

        $response = $this->actingAs($user)->get(route('dashboard', ['period' => 'today']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('dashboard.filters.period', 'today'));
    }
}