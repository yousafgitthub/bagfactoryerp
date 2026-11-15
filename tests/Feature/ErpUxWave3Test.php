<?php

namespace Tests\Feature;

use App\Models\ProductionHall;
use App\Models\User;
use App\Support\MasterDataRegistry;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErpUxWave3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_master_data_index_columns_omit_numeric_id(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('owner-admin');

        ProductionHall::query()->create([
            'code' => 'H-W3',
            'name' => 'Wave 3 hall',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('master-data.index', 'production-halls'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('MasterData/Index')
                ->has('columns', 3)
                ->where('columns.0.key', 'code')
                ->where('columns.1.key', 'name')
                ->where('columns.2.key', 'status'));
    }

    public function test_master_data_registry_filters_id_column_when_code_exists(): void
    {
        $config = MasterDataRegistry::get('production-halls');
        $config['columns'] = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'name', 'label' => 'Name'],
        ];

        $columns = MasterDataRegistry::tableColumnsForIndex($config);

        $this->assertSame(['code', 'name'], collect($columns)->pluck('key')->all());
    }

    public function test_payroll_advance_create_renders_form(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('hr-manager');

        $this->actingAs($user)
            ->get(route('hr.payroll.advances.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Hr/Payroll/Advances/Form')
                ->has('lookups.employees'));
    }

    public function test_payroll_loan_create_renders_form(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole('hr-manager');

        $this->actingAs($user)
            ->get(route('hr.payroll.loans.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Hr/Payroll/Loans/Form')
                ->has('lookups.employees'));
    }
}