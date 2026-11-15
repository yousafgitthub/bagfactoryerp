<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardDataService
{
    /**
     * @param  array{period?: string, from?: string|null, to?: string|null}  $filters
     * @return array<string, mixed>
     */
    public function forUser(?User $user, array $filters = []): array
    {
        $range = DashboardDateRange::fromFilters($filters);
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();
        $today = now()->toDateString();

        return [
            'filters' => [
                'period' => $range->period,
                'from' => $range->start->toDateString(),
                'to' => $range->end->toDateString(),
            ],
            'summary' => $this->summaryCards($today, $monthStart, $monthEnd),
            'production_trend' => $this->productionTrend($range),
            'dana_vs_bags' => $this->danaVersusBags($range),
            'monthly_sales_expenses' => $this->monthlySalesExpenses(),
            'machine_wise_production' => $this->machineWiseProduction($range),
            'material_consumption' => $this->materialConsumption($range),
            'waste' => $this->wasteMetrics($range),
            'inventory_alerts' => $this->inventoryAlerts(),
            'recent_transactions' => $this->recentTransactions(),
            'machine_performance' => $this->machinePerformance(),
            'payroll_overview' => $this->payrollOverview(),
            'financial_overview' => $this->financialOverview(),
            'team' => $this->teamOverview($user),
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: string|int|float, hint: string}>
     */
    private function summaryCards(string $today, string $monthStart, string $monthEnd): array
    {
        return [
            [
                'key' => 'raw_material_stock',
                'label' => 'Raw material stock',
                'value' => $this->sumBalance('raw'),
                'hint' => 'Unposted ledger balances when configured',
            ],
            [
                'key' => 'dana_stock_kg',
                'label' => 'Dana stock (KG)',
                'value' => $this->sumBalance('dana'),
                'hint' => 'Dana finished goods inventory',
            ],
            [
                'key' => 'finished_bag_stock',
                'label' => 'Finished bag stock (pcs)',
                'value' => $this->sumBalance('finished_bag'),
                'hint' => 'Saleable shopping bags',
            ],
            [
                'key' => 'today_dana_production',
                'label' => "Today's Dana production",
                'value' => $this->sumWhenTable('dana_batches', fn () => (float) DB::table('dana_batches')->whereDate('posted_at', $today)->sum('output_kg')),
                'hint' => 'Posted batches',
            ],
            [
                'key' => 'today_bag_production',
                'label' => "Today's bag production",
                'value' => $this->sumWhenTable('shopping_bag_batches', fn () => (int) DB::table('shopping_bag_batches')->whereDate('posted_at', $today)->sum('output_pieces')),
                'hint' => 'Posted batches',
            ],
            [
                'key' => 'today_wastage',
                'label' => "Today's wastage",
                'value' => $this->todayWastage($today),
                'hint' => 'Process loss posted today',
            ],
            [
                'key' => 'active_employees',
                'label' => 'Active employees',
                'value' => $this->countWhenTableExists('employees', fn () => DB::table('employees')->where('is_active', true)->count()),
                'hint' => 'HR master',
            ],
            [
                'key' => 'today_attendance',
                'label' => "Today's attendance",
                'value' => $this->countWhenTableExists('attendance_records', fn () => DB::table('attendance_records')->whereDate('attendance_date', $today)->count()),
                'hint' => 'Check-ins recorded',
            ],
            [
                'key' => 'month_payroll_expense',
                'label' => 'Month payroll expense',
                'value' => $this->monthAmount('payroll_runs', 'total_net', $monthStart, $monthEnd, 'period_end'),
                'hint' => 'Approved payroll in month',
            ],
            [
                'key' => 'month_purchases',
                'label' => 'Month purchases',
                'value' => $this->monthAmount('purchase_invoices', 'grand_total', $monthStart, $monthEnd),
                'hint' => 'Posted supplier invoices',
            ],
            [
                'key' => 'month_sales',
                'label' => 'Month sales',
                'value' => $this->monthAmount('sales_invoices', 'grand_total', $monthStart, $monthEnd),
                'hint' => 'Posted customer invoices',
            ],
            [
                'key' => 'month_operating_expenses',
                'label' => 'Month operating expenses',
                'value' => $this->monthAmount('expenses', 'amount', $monthStart, $monthEnd),
                'hint' => 'Posted operating expenses',
            ],
        ];
    }

    private function sumBalance(string $bucket): float|int
    {
        if (! Schema::hasTable('inventory_balances')) {
            return 0;
        }

        if (Schema::hasColumn('inventory_balances', 'bucket')) {
            return (float) DB::table('inventory_balances')->where('bucket', $bucket)->sum('quantity');
        }

        return 0;
    }

    private function todayWastage(string $today): float
    {
        $total = 0.0;

        foreach (['dana_batch_wastes', 'shopping_bag_batch_wastes'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $total += (float) DB::table($table)->whereDate('recorded_at', $today)->sum('quantity');
        }

        return $total;
    }

    /**
     * @return array{points: list<array{label: string, date: string, dana_kg: float, bags_pcs: int}>}
     */
    private function productionTrend(DashboardDateRange $range): array
    {
        $points = [];

        foreach ($range->days() as $day) {
            $danaKg = Schema::hasTable('dana_batches')
                ? (float) DB::table('dana_batches')->whereDate('posted_at', $day['date'])->sum('output_kg')
                : 0.0;

            $bagsPcs = Schema::hasTable('shopping_bag_batches')
                ? (int) DB::table('shopping_bag_batches')->whereDate('posted_at', $day['date'])->sum('output_pieces')
                : 0;

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
     * @return array{totals: array{dana_kg: float, bags_pcs: int}}
     */
    private function danaVersusBags(DashboardDateRange $range): array
    {
        $dana = 0.0;
        $bags = 0;

        if (Schema::hasTable('dana_batches')) {
            $dana = (float) DB::table('dana_batches')
                ->whereBetween('posted_at', [$range->start, $range->end])
                ->sum('output_kg');
        }

        if (Schema::hasTable('shopping_bag_batches')) {
            $bags = (int) DB::table('shopping_bag_batches')
                ->whereBetween('posted_at', [$range->start, $range->end])
                ->sum('output_pieces');
        }

        return ['totals' => ['dana_kg' => $dana, 'bags_pcs' => $bags]];
    }

    /**
     * @return list<array{month: string, sales: float, expenses: float}>
     */
    private function monthlySalesExpenses(): array
    {
        $months = Collection::times(6, function (int $i) {
            return now()->subMonths(6 - $i)->startOfMonth();
        });

        return $months->map(function ($month) {
            $label = $month->format('M Y');
            $start = $month->copy()->startOfMonth();
            $end = $month->copy()->endOfMonth();

            $sales = Schema::hasTable('sales_invoices')
                ? (float) DB::table('sales_invoices')->whereBetween('invoice_date', [$start, $end])->sum('grand_total')
                : 0.0;

            $expenses = Schema::hasTable('expenses')
                ? (float) DB::table('expenses')->whereBetween('expense_date', [$start, $end])->sum('amount')
                : 0.0;

            return [
                'month' => $label,
                'sales' => $sales,
                'expenses' => $expenses,
            ];
        })->all();
    }

    /**
     * @return list<array{machine: string, dana_kg: float, bags_pcs: int}>
     */
    private function machineWiseProduction(DashboardDateRange $range): array
    {
        if (! Schema::hasTable('machines')) {
            return [];
        }

        if (Schema::hasTable('dana_batches')) {
            return DB::table('machines')
                ->leftJoin('dana_batches', function ($join) use ($range) {
                    $join->on('dana_batches.machine_id', '=', 'machines.id')
                        ->whereBetween('dana_batches.posted_at', [$range->start, $range->end]);
                })
                ->select('machines.name')
                ->selectRaw('COALESCE(SUM(dana_batches.output_kg), 0) as dana_kg')
                ->groupBy('machines.id', 'machines.name')
                ->orderBy('machines.name')
                ->limit(8)
                ->get()
                ->map(fn ($row) => [
                    'machine' => (string) $row->name,
                    'dana_kg' => (float) $row->dana_kg,
                    'bags_pcs' => 0,
                ])
                ->all();
        }

        return DB::table('machines')
            ->orderBy('name')
            ->limit(8)
            ->get(['name'])
            ->map(fn ($row) => [
                'machine' => (string) $row->name,
                'dana_kg' => 0.0,
                'bags_pcs' => 0,
            ])
            ->all();
    }

    /**
     * @return list<array{material: string, quantity: float}>
     */
    private function materialConsumption(DashboardDateRange $range): array
    {
        if (! Schema::hasTable('inventory_ledger_entries') || ! Schema::hasTable('materials')) {
            return [];
        }

        return DB::table('inventory_ledger_entries')
            ->join('materials', 'materials.id', '=', 'inventory_ledger_entries.material_id')
            ->where('inventory_ledger_entries.movement_type', 'issue')
            ->whereBetween('inventory_ledger_entries.occurred_at', [$range->start, $range->end])
            ->groupBy('materials.id', 'materials.name')
            ->selectRaw('materials.name as material, SUM(ABS(inventory_ledger_entries.quantity)) as quantity')
            ->orderByDesc('quantity')
            ->limit(8)
            ->get()
            ->map(fn ($row) => [
                'material' => (string) $row->material,
                'quantity' => (float) $row->quantity,
            ])
            ->all();
    }

    /**
     * @return array{quantity: float, percentage: float}
     */
    private function wasteMetrics(DashboardDateRange $range): array
    {
        $wasteQty = 0.0;
        $productionQty = 0.0;

        if (Schema::hasTable('dana_batch_wastes')) {
            $wasteQty += (float) DB::table('dana_batch_wastes')
                ->whereBetween('recorded_at', [$range->start, $range->end])
                ->sum('quantity');
        }

        if (Schema::hasTable('dana_batches')) {
            $productionQty += (float) DB::table('dana_batches')
                ->whereBetween('posted_at', [$range->start, $range->end])
                ->sum('output_kg');
        }

        $percentage = $productionQty > 0 ? round(($wasteQty / $productionQty) * 100, 2) : 0.0;

        return [
            'quantity' => $wasteQty,
            'percentage' => $percentage,
        ];
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
            ->orderBy('name')
            ->limit(6)
            ->get(['name', 'status'])
            ->map(fn ($row) => [
                'name' => (string) $row->name,
                'utilization_pct' => 0.0,
                'status' => (string) ($row->status ?? 'active'),
            ])
            ->all();
    }

    /**
     * @return array{headcount: int, draft_runs: int, last_run_period: string|null}
     */
    private function payrollOverview(): array
    {
        $headcount = $this->countWhenTableExists(
            'employees',
            fn () => DB::table('employees')->where('is_active', true)->count(),
        );

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
            'roles' => \Spatie\Permission\Models\Role::query()->count(),
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

    private function sumWhenTable(string $table, callable $counter): float|int
    {
        if (! Schema::hasTable($table)) {
            return 0;
        }

        return $counter();
    }

    private function monthAmount(string $table, string $column, string $start, string $end, ?string $dateColumn = 'invoice_date'): float
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0.0;
        }

        if (! Schema::hasColumn($table, $dateColumn)) {
            return 0.0;
        }

        return (float) DB::table($table)
            ->whereBetween($dateColumn, [$start, $end])
            ->sum($column);
    }
}