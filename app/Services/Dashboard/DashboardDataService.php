<?php

namespace App\Services\Dashboard;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardDataService
{
    /** @var array<string, bool> */
    private array $knownTables = [];

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
        $activeEmployees = $this->countWhenTableExists(
            'employees',
            fn () => DB::table('employees')->where('is_active', true)->count(),
        );

        return [
            'filters' => [
                'period' => $range->period,
                'from' => $range->start->toDateString(),
                'to' => $range->end->toDateString(),
            ],
            'summary' => $this->summaryCards($today, $monthStart, $monthEnd, $activeEmployees),
            'production_trend' => $this->productionTrend($range),
            'dana_vs_bags' => $this->danaVersusBags($range),
            'monthly_sales_expenses' => $this->monthlySalesExpenses(),
            'machine_wise_production' => $this->machineWiseProduction($range),
            'material_consumption' => $this->materialConsumption($range),
            'waste' => $this->wasteMetrics($range),
            'inventory_alerts' => $this->inventoryAlerts(),
            'recent_transactions' => $this->recentTransactions(),
            'machine_performance' => $this->machinePerformance(),
            'payroll_overview' => $this->payrollOverview($activeEmployees),
            'financial_overview' => $this->financialOverview(),
            'team' => $this->teamOverview($user),
        ];
    }

    /**
     * @return list<array{key: string, label: string, value: string|int|float, hint: string}>
     */
    private function summaryCards(string $today, string $monthStart, string $monthEnd, int $activeEmployees): array
    {
        $balances = $this->balanceTotals();

        return [
            [
                'key' => 'raw_material_stock',
                'label' => 'Raw material stock',
                'value' => (float) ($balances['raw'] ?? 0),
                'hint' => 'Granules, additives, and packaging on hand',
            ],
            [
                'key' => 'dana_stock_kg',
                'label' => 'Dana stock (KG)',
                'value' => (float) ($balances['dana'] ?? 0),
                'hint' => 'Dana finished goods inventory',
            ],
            [
                'key' => 'finished_bag_stock',
                'label' => 'Finished bag stock (pcs)',
                'value' => (float) ($balances['finished_bag'] ?? 0),
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
                'value' => $this->sumWhenTable('bag_production_batches', fn () => (int) DB::table('bag_production_batches')->whereDate('posted_at', $today)->sum('good_quantity')),
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
                'value' => $activeEmployees,
                'hint' => 'HR master',
            ],
            [
                'key' => 'today_attendance',
                'label' => "Today's attendance",
                'value' => $this->countWhenTableExists('attendances', fn () => DB::table('attendances')->whereDate('attendance_date', $today)->count()),
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
                'value' => $this->monthAmount('expenses', 'amount', $monthStart, $monthEnd, 'expense_date'),
                'hint' => 'Posted operating expenses',
            ],
        ];
    }

    /**
     * @return array<string, float|int|string>
     */
    private function balanceTotals(): array
    {
        if (! $this->tableExists('inventory_balances') || ! Schema::hasColumn('inventory_balances', 'bucket')) {
            return [];
        }

        return DB::table('inventory_balances')
            ->selectRaw('bucket, SUM(quantity) as qty')
            ->groupBy('bucket')
            ->get()
            ->mapWithKeys(fn ($row) => [(string) $row->bucket => $row->qty])
            ->all();
    }

    private function todayWastage(string $today): float
    {
        $total = 0.0;

        foreach (['dana_batch_wastes' => 'quantity', 'bag_batch_wastes' => 'quantity_kg'] as $table => $column) {
            if (! $this->tableExists($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $total += (float) DB::table($table)->whereDate('recorded_at', $today)->sum($column);
        }

        return $total;
    }

    /**
     * @return array{points: list<array{label: string, date: string, dana_kg: float, bags_pcs: int}>}
     */
    private function productionTrend(DashboardDateRange $range): array
    {
        $danaByDay = $this->sumColumnByDay('dana_batches', 'posted_at', 'output_kg', $range);
        $bagsByDay = $this->sumColumnByDay('bag_production_batches', 'posted_at', 'good_quantity', $range);
        $points = [];

        foreach ($range->days() as $day) {
            $points[] = [
                'label' => $day['label'],
                'date' => $day['date'],
                'dana_kg' => (float) ($danaByDay[$day['date']] ?? 0),
                'bags_pcs' => (int) ($bagsByDay[$day['date']] ?? 0),
            ];
        }

        return ['points' => $points];
    }

    /**
     * One grouped sum per calendar day. Matches the previous per-day whereDate totals.
     *
     * @return array<string, float|int|string>
     */
    private function sumColumnByDay(string $table, string $dateColumn, string $sumColumn, DashboardDateRange $range): array
    {
        if (! $this->tableExists($table)) {
            return [];
        }

        $day = $this->calendarDaySql($dateColumn);
        $endExclusive = $range->end->copy()->startOfDay()->addDay();

        return DB::table($table)
            ->where($dateColumn, '>=', $range->start->copy()->startOfDay())
            ->where($dateColumn, '<', $endExclusive)
            ->selectRaw("{$day} as day, SUM({$sumColumn}) as total")
            ->groupByRaw($day)
            ->get()
            ->mapWithKeys(fn ($row) => [substr((string) $row->day, 0, 10) => $row->total])
            ->all();
    }

    private function calendarDaySql(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m-%d', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM-DD')",
            default => "DATE({$column})",
        };
    }

    private function calendarMonthSql(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', {$column})",
            'pgsql' => "to_char({$column}, 'YYYY-MM')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    /**
     * @return array{totals: array{dana_kg: float, bags_pcs: int}}
     */
    private function danaVersusBags(DashboardDateRange $range): array
    {
        $dana = 0.0;
        $bags = 0;

        if ($this->tableExists('dana_batches')) {
            $dana = (float) DB::table('dana_batches')
                ->whereBetween('posted_at', [$range->start, $range->end])
                ->sum('output_kg');
        }

        if ($this->tableExists('bag_production_batches')) {
            $bags = (int) DB::table('bag_production_batches')
                ->whereBetween('posted_at', [$range->start, $range->end])
                ->sum('good_quantity');
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

        $start = $months->first()->copy()->startOfMonth();
        $endExclusive = $months->last()->copy()->startOfMonth()->addMonth();
        $salesByMonth = $this->sumColumnByMonth('sales_invoices', 'invoice_date', 'grand_total', $start, $endExclusive);
        $expensesByMonth = $this->sumColumnByMonth('expenses', 'expense_date', 'amount', $start, $endExclusive);

        return $months->map(function ($month) use ($salesByMonth, $expensesByMonth) {
            $key = $month->format('Y-m');

            return [
                'month' => $month->format('M Y'),
                'sales' => (float) ($salesByMonth[$key] ?? 0),
                'expenses' => (float) ($expensesByMonth[$key] ?? 0),
            ];
        })->all();
    }

    /**
     * @return array<string, float|int|string>
     */
    private function sumColumnByMonth(string $table, string $dateColumn, string $sumColumn, \Carbon\Carbon $start, \Carbon\Carbon $endExclusive): array
    {
        if (! $this->tableExists($table)) {
            return [];
        }

        $month = $this->calendarMonthSql($dateColumn);

        return DB::table($table)
            ->where($dateColumn, '>=', $start->toDateString())
            ->where($dateColumn, '<', $endExclusive->toDateString())
            ->selectRaw("{$month} as month_key, SUM({$sumColumn}) as total")
            ->groupByRaw($month)
            ->get()
            ->mapWithKeys(fn ($row) => [substr((string) $row->month_key, 0, 7) => $row->total])
            ->all();
    }

    /**
     * @return list<array{machine: string, dana_kg: float, bags_pcs: int}>
     */
    private function machineWiseProduction(DashboardDateRange $range): array
    {
        if (! $this->tableExists('machines')) {
            return [];
        }

        if ($this->tableExists('dana_batches')) {
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
        if (! $this->tableExists('inventory_ledger_entries') || ! $this->tableExists('materials')) {
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

        if ($this->tableExists('dana_batch_wastes')) {
            $wasteQty += (float) DB::table('dana_batch_wastes')
                ->whereBetween('recorded_at', [$range->start, $range->end])
                ->sum('quantity');
        }

        if ($this->tableExists('dana_batches')) {
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
        if (! $this->tableExists('inventory_balances') || ! $this->tableExists('materials')) {
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
        if (! $this->tableExists('inventory_ledger_entries')) {
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
        if (! $this->tableExists('machines')) {
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
    private function payrollOverview(int $activeEmployees): array
    {
        $draftRuns = $this->countWhenTableExists(
            'payroll_runs',
            fn () => DB::table('payroll_runs')->where('status', 'draft')->count(),
        );

        $lastPeriod = null;
        if ($this->tableExists('payroll_runs')) {
            $last = DB::table('payroll_runs')->orderByDesc('period_end')->value('period_label');
            $lastPeriod = $last !== null ? (string) $last : null;
        }

        return [
            'headcount' => $activeEmployees,
            'draft_runs' => $draftRuns,
            'last_run_period' => $lastPeriod,
        ];
    }

    /**
     * @return array{cash_on_hand: float|null, receivables: float|null, payables: float|null}
     */
    private function financialOverview(): array
    {
        $empty = [
            'cash_on_hand' => null,
            'receivables' => null,
            'payables' => null,
        ];

        if (! $this->tableExists('gl_journal_lines') || ! $this->tableExists('gl_journal_entries') || ! $this->tableExists('gl_accounts')) {
            return $empty;
        }

        $codes = [
            'cash_on_hand' => (string) config('accounting.account_codes.cash'),
            'receivables' => (string) config('accounting.account_codes.accounts_receivable'),
            'payables' => (string) config('accounting.account_codes.accounts_payable'),
        ];

        $rows = DB::table('gl_journal_lines')
            ->join('gl_accounts', 'gl_accounts.id', '=', 'gl_journal_lines.gl_account_id')
            ->join('gl_journal_entries', 'gl_journal_entries.id', '=', 'gl_journal_lines.gl_journal_entry_id')
            ->where('gl_journal_entries.status', 'posted')
            ->whereIn('gl_accounts.code', array_values($codes))
            ->groupBy('gl_accounts.code', 'gl_accounts.normal_balance')
            ->selectRaw('gl_accounts.code as code, gl_accounts.normal_balance as normal_balance, SUM(gl_journal_lines.debit) as debit, SUM(gl_journal_lines.credit) as credit')
            ->get()
            ->keyBy(fn ($row) => (string) $row->code);

        $overview = [];

        foreach ($codes as $key => $code) {
            $row = $rows->get($code);
            if ($row === null) {
                $overview[$key] = 0.0;

                continue;
            }

            $overview[$key] = $row->normal_balance === 'debit'
                ? round((float) $row->debit - (float) $row->credit, 2)
                : round((float) $row->credit - (float) $row->debit, 2);
        }

        return $overview;
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
        if (! $this->tableExists($table)) {
            return 0;
        }

        return (int) $counter();
    }

    private function sumWhenTable(string $table, callable $counter): float|int
    {
        if (! $this->tableExists($table)) {
            return 0;
        }

        return $counter();
    }

    private function monthAmount(string $table, string $column, string $start, string $end, ?string $dateColumn = 'invoice_date'): float
    {
        if (! $this->tableExists($table) || ! Schema::hasColumn($table, $column)) {
            return 0.0;
        }

        if (! Schema::hasColumn($table, $dateColumn)) {
            return 0.0;
        }

        return (float) DB::table($table)
            ->whereBetween($dateColumn, [$start, $end])
            ->sum($column);
    }

    private function tableExists(string $table): bool
    {
        if (! array_key_exists($table, $this->knownTables)) {
            $this->knownTables[$table] = Schema::hasTable($table);
        }

        return $this->knownTables[$table];
    }
}