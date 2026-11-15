<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class DashboardDataService
{
    /**
     * @return array<string, mixed>
     */
    public function forUser(?User $user): array
    {
        return [
            'summary' => $this->summaryCards(),
            'production_trend' => $this->productionTrend(),
            'inventory_alerts' => $this->inventoryAlerts(),
            'recent_transactions' => $this->recentTransactions(),
            'machine_performance' => $this->machinePerformance(),
            'payroll_overview' => $this->payrollOverview(),
            'financial_overview' => $this->financialOverview(),
            'team' => $this->teamOverview($user),
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: string|int, hint: string, trend: string|null}>
     */
    private function summaryCards(): array
    {
        $activeUsers = User::query()->count();

        return [
            [
                'key' => 'active_users',
                'label' => 'System users',
                'value' => $activeUsers,
                'hint' => 'Registered ERP accounts',
                'trend' => null,
            ],
            [
                'key' => 'open_production_orders',
                'label' => 'Open production',
                'value' => $this->countWhenTableExists('production_orders', fn () => DB::table('production_orders')->where('status', 'open')->count()),
                'hint' => 'Awaiting shop-floor posting',
                'trend' => null,
            ],
            [
                'key' => 'inventory_skus',
                'label' => 'Inventory SKUs',
                'value' => $this->countWhenTableExists('materials', fn () => DB::table('materials')->whereNull('deleted_at')->count()),
                'hint' => 'Materials & variants tracked',
                'trend' => null,
            ],
            [
                'key' => 'pending_payroll',
                'label' => 'Payroll periods',
                'value' => $this->countWhenTableExists('payroll_runs', fn () => DB::table('payroll_runs')->where('status', 'draft')->count()),
                'hint' => 'Draft payroll awaiting approval',
                'trend' => null,
            ],
        ];
    }

    /**
     * @return array{points: list<array{label: string, date: string, dana_kg: float, bags_pcs: int}>}
     */
    private function productionTrend(): array
    {
        $points = [];

        foreach ($this->lastSevenDays() as $day) {
            $danaKg = 0.0;
            $bagsPcs = 0;

            if (Schema::hasTable('dana_batches')) {
                $danaKg = (float) DB::table('dana_batches')
                    ->whereDate('posted_at', $day['date'])
                    ->sum('output_kg');
            }

            if (Schema::hasTable('shopping_bag_batches')) {
                $bagsPcs = (int) DB::table('shopping_bag_batches')
                    ->whereDate('posted_at', $day['date'])
                    ->sum('output_pieces');
            }

            $points[] = [
                'label' => $day['label'],
                'date' => $day['date'],
                'dana_kg' => $danaKg,
                'bags_pcs' => $bagsPcs,
            ];
        }

        return ['points' => $points];
    }

    /**
     * @return list<array{severity: string, title: string, detail: string}>
     */
    private function inventoryAlerts(): array
    {
        if (! Schema::hasTable('inventory_balances') || ! Schema::hasTable('materials')) {
            return [];
        }

        $lowStock = DB::table('inventory_balances')
            ->join('materials', 'materials.id', '=', 'inventory_balances.material_id')
            ->whereColumn('inventory_balances.quantity', '<', 'materials.reorder_level')
            ->select('materials.name', 'inventory_balances.quantity', 'materials.reorder_level')
            ->limit(5)
            ->get();

        return $lowStock->map(fn ($row) => [
            'severity' => 'warning',
            'title' => (string) $row->name,
            'detail' => sprintf('On hand %s (reorder %s)', $row->quantity, $row->reorder_level),
        ])->all();
    }

    /**
     * @return list<array{type: string, reference: string, occurred_at: string, amount: string|null}>
     */
    private function recentTransactions(): array
    {
        if (! Schema::hasTable('inventory_ledger_entries')) {
            return [];
        }

        return DB::table('inventory_ledger_entries')
            ->orderByDesc('occurred_at')
            ->limit(8)
            ->get(['movement_type', 'reference', 'occurred_at', 'quantity'])
            ->map(fn ($row) => [
                'type' => (string) $row->movement_type,
                'reference' => (string) ($row->reference ?? '—'),
                'occurred_at' => (string) $row->occurred_at,
                'amount' => $row->quantity !== null ? (string) $row->quantity : null,
            ])
            ->all();
    }

    /**
     * @return list<array{name: string, utilization_pct: float, status: string}>
     */
    private function machinePerformance(): array
    {
        if (! Schema::hasTable('machines')) {
            return [];
        }

        return DB::table('machines')
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->limit(6)
            ->get(['name', 'status'])
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'utilization_pct' => 0.0,
                'status' => (string) ($row->status ?? 'idle'),
            ])
            ->all();
    }

    /**
     * @return array{headcount: int, draft_runs: int, last_run_period: string|null}
     */
    private function payrollOverview(): array
    {
        $headcount = Schema::hasTable('employees')
            ? (int) DB::table('employees')->whereNull('deleted_at')->where('is_active', true)->count()
            : 0;

        $draftRuns = $this->countWhenTableExists(
            'payroll_runs',
            fn () => DB::table('payroll_runs')->where('status', 'draft')->count(),
        );

        $lastPeriod = null;
        if (Schema::hasTable('payroll_runs')) {
            $last = DB::table('payroll_runs')->orderByDesc('period_end')->value('period_label');
            $lastPeriod = $last !== null ? (string) $last : null;
        }

        return [
            'headcount' => $headcount,
            'draft_runs' => $draftRuns,
            'last_run_period' => $lastPeriod,
        ];
    }

    /**
     * @return array{cash_on_hand: float|null, receivables: float|null, payables: float|null}
     */
    private function financialOverview(): array
    {
        if (! Schema::hasTable('gl_account_balances')) {
            return [
                'cash_on_hand' => null,
                'receivables' => null,
                'payables' => null,
            ];
        }

        return [
            'cash_on_hand' => (float) DB::table('gl_account_balances')->where('account_code', 'cash')->value('balance'),
            'receivables' => (float) DB::table('gl_account_balances')->where('account_code', 'ar')->value('balance'),
            'payables' => (float) DB::table('gl_account_balances')->where('account_code', 'ap')->value('balance'),
        ];
    }

    /**
     * @return array{roles: int, your_role: string|null}
     */
    private function teamOverview(?User $user): array
    {
        return [
            'roles' => Role::query()->count(),
            'your_role' => $user?->getRoleNames()->first(),
        ];
    }

    private function countWhenTableExists(string $table, callable $counter): int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return (int) $counter();
    }

    /**
     * @return list<array{label: string, date: string}>
     */
    private function lastSevenDays(): array
    {
        return Collection::times(7, function (int $index) {
            $date = now()->subDays(6 - ($index - 1))->startOfDay();

            return [
                'label' => $date->format('D'),
                'date' => $date->toDateString(),
            ];
        })->all();
    }
}