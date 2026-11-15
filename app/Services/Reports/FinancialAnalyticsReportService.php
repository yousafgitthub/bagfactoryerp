<?php

namespace App\Services\Reports;

use App\Models\BagProductionBatch;
use App\Models\DanaBatch;
use App\Models\Expense;
use App\Models\GlAccount;
use App\Models\SalesInvoice;
use App\Models\TreasuryAccount;
use App\Services\Accounting\FinancialReportService;
use App\Services\Reports\Concerns\PaginatesReportRows;
use Illuminate\Support\Facades\DB;

class FinancialAnalyticsReportService
{
    use PaginatesReportRows;

    public function __construct(private readonly FinancialReportService $financial)
    {
    }

    public function expenses(ReportFilters $filters): ReportResult
    {
        $all = Expense::query()
            ->with('category:id,name')
            ->whereNotNull('gl_journal_entry_id')
            ->when($filters->from, fn ($q) => $q->whereDate('expense_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('expense_date', '<=', $filters->to))
            ->orderByDesc('expense_date')
            ->get()
            ->map(fn ($e) => [
                'date' => $e->expense_date?->toDateString(),
                'reference' => $e->expense_number,
                'category' => $e->category?->name ?? '—',
                'amount' => round((float) $e->amount, 2),
                'paid' => $e->payment_status,
            ])->all();

        return new ReportResult(
            title: 'Daily / monthly expenses',
            description: 'Posted operating expenses (excludes inventory purchases).',
            columns: [
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'reference', 'label' => 'Expense #', 'sortable' => true],
                ['key' => 'category', 'label' => 'Category', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
                ['key' => 'paid', 'label' => 'Payment', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: ['expense_total' => round(array_sum(array_column($all, 'amount')), 2)],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function cashbook(ReportFilters $filters): ReportResult
    {
        return $this->treasuryBook('cash', $filters, 'Cashbook', 'Treasury cash movements.');
    }

    public function bankbook(ReportFilters $filters): ReportResult
    {
        return $this->treasuryBook('bank', $filters, 'Bank book', 'Treasury bank movements.');
    }

    public function trialBalance(ReportFilters $filters): ReportResult
    {
        $rows = $this->financial->trialBalance($filters->from, $filters->to);
        $all = $rows->map(fn ($r) => [
            'account' => $r->account->code.' '.$r->account->name,
            'debit' => round($r->debit, 2),
            'credit' => round($r->credit, 2),
            'balance' => round($r->balance, 2),
        ])->all();

        return new ReportResult(
            title: 'Trial balance',
            description: 'Posted GL journals — must tie to ledger detail.',
            columns: [
                ['key' => 'account', 'label' => 'Account', 'sortable' => true],
                ['key' => 'debit', 'label' => 'Debit', 'sortable' => true],
                ['key' => 'credit', 'label' => 'Credit', 'sortable' => true],
                ['key' => 'balance', 'label' => 'Balance', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function profitAndLoss(ReportFilters $filters): ReportResult
    {
        $pnl = $this->financial->profitAndLoss($filters->from, $filters->to);
        $all = $pnl['lines']->map(fn ($line) => [
            'account' => $line['account']->code.' '.$line['account']->name,
            'type' => $line['account']->type,
            'amount' => $line['amount'],
        ])->all();

        return new ReportResult(
            title: 'Profit & loss',
            description: 'Revenue and expense accounts from posted journals.',
            columns: [
                ['key' => 'account', 'label' => 'Account', 'sortable' => true],
                ['key' => 'type', 'label' => 'Type', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: [
                'revenue' => $pnl['revenue'],
                'expenses' => $pnl['expenses'],
                'net_profit' => $pnl['net_profit'],
            ],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function balanceSheet(ReportFilters $filters): ReportResult
    {
        $sheet = $this->financial->balanceSheet($filters->asOf ?? $filters->to);
        $all = $sheet['lines']->map(fn ($line) => [
            'account' => $line['account']->code.' '.$line['account']->name,
            'type' => $line['account']->type,
            'amount' => $line['amount'],
        ])->all();

        return new ReportResult(
            title: 'Balance sheet',
            description: 'Assets, liabilities and equity as of selected date.',
            columns: [
                ['key' => 'account', 'label' => 'Account', 'sortable' => true],
                ['key' => 'type', 'label' => 'Type', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: [
                'assets' => $sheet['assets'],
                'liabilities' => $sheet['liabilities'],
                'equity' => $sheet['equity'],
                'balanced' => $sheet['balanced'],
            ],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function productionCost(ReportFilters $filters): ReportResult
    {
        $dana = DanaBatch::query()
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->sum('total_batch_cost');

        $bag = BagProductionBatch::query()
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->sum('total_batch_cost');

        $all = [
            [
                'line' => 'Dana production',
                'batch_cost' => round((float) $dana, 2),
                'note' => 'Capitalized to inventory when posted — not operating expense.',
            ],
            [
                'line' => 'Bag production',
                'batch_cost' => round((float) $bag, 2),
                'note' => 'Capitalized to finished goods — COGS recognized on sale.',
            ],
        ];

        return new ReportResult(
            title: 'Production cost',
            description: 'Posted batch costs (inventory capitalization, not P&L expense).',
            columns: [
                ['key' => 'line', 'label' => 'Line', 'sortable' => true],
                ['key' => 'batch_cost', 'label' => 'Total batch cost', 'sortable' => true],
                ['key' => 'note', 'label' => 'Note', 'sortable' => false],
            ],
            rows: $all,
            filters: $filters->toArray(),
            summary: ['combined_production_cost' => round((float) $dana + (float) $bag, 2)],
            totalRows: 2,
        );
    }

    public function costPerKgDana(ReportFilters $filters): ReportResult
    {
        $query = DanaBatch::query()
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->orderByDesc('production_date')
            ->limit(500);

        $all = $query->get()->map(fn ($b) => [
            'batch' => $b->batch_number,
            'date' => $b->production_date?->toDateString(),
            'output_kg' => round((float) $b->output_kg, 2),
            'total_cost' => round((float) $b->total_batch_cost, 2),
            'cost_per_kg' => round((float) $b->cost_per_kg, 4),
        ])->all();

        return new ReportResult(
            title: 'Cost per KG of Dana',
            description: 'From posted Dana batch costing.',
            columns: [
                ['key' => 'batch', 'label' => 'Batch', 'sortable' => true],
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'output_kg', 'label' => 'Output kg', 'sortable' => true],
                ['key' => 'total_cost', 'label' => 'Total cost', 'sortable' => true],
                ['key' => 'cost_per_kg', 'label' => 'Cost / kg', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function costPerBag(ReportFilters $filters): ReportResult
    {
        $query = BagProductionBatch::query()
            ->where('status', 'posted')
            ->when($filters->from, fn ($q) => $q->whereDate('production_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('production_date', '<=', $filters->to))
            ->orderByDesc('production_date')
            ->limit(500);

        $all = $query->get()->map(fn ($b) => [
            'batch' => $b->batch_number,
            'date' => $b->production_date?->toDateString(),
            'good_bags' => round((float) $b->good_quantity, 2),
            'total_cost' => round((float) $b->total_batch_cost, 2),
            'cost_per_bag' => round((float) $b->cost_per_good_bag, 4),
        ])->all();

        return new ReportResult(
            title: 'Cost per shopping bag',
            description: 'Posted bag batch unit cost.',
            columns: [
                ['key' => 'batch', 'label' => 'Batch', 'sortable' => true],
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'good_bags', 'label' => 'Good bags', 'sortable' => true],
                ['key' => 'total_cost', 'label' => 'Total cost', 'sortable' => true],
                ['key' => 'cost_per_bag', 'label' => 'Cost / bag', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function grossNetProfit(ReportFilters $filters): ReportResult
    {
        $sales = SalesInvoice::query()
            ->where('status', '!=', 'draft')
            ->when($filters->from, fn ($q) => $q->whereDate('invoice_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('invoice_date', '<=', $filters->to))
            ->selectRaw('SUM(grand_total) as revenue, SUM(cogs_total) as cogs')
            ->first();

        $revenue = (float) ($sales->revenue ?? 0);
        $cogs = (float) ($sales->cogs ?? 0);
        $gross = $revenue - $cogs;

        $pnl = $this->financial->profitAndLoss($filters->from, $filters->to);
        $operatingExpenses = $pnl['expenses'];

        $all = [
            [
                'metric' => 'Sales revenue (invoices)',
                'amount' => round($revenue, 2),
                'source' => 'sales_invoices',
            ],
            [
                'metric' => 'COGS (invoice WAC)',
                'amount' => round($cogs, 2),
                'source' => 'sales_invoices.cogs_total',
            ],
            [
                'metric' => 'Gross profit (sales)',
                'amount' => round($gross, 2),
                'source' => 'Revenue − COGS — not GL revenue if tax/discount differs',
            ],
            [
                'metric' => 'Operating expenses (GL)',
                'amount' => round($operatingExpenses, 2),
                'source' => 'GL expense accounts — excludes inventory purchases',
            ],
            [
                'metric' => 'Net profit (GL)',
                'amount' => $pnl['net_profit'],
                'source' => 'GL revenue − GL expenses',
            ],
        ];

        return new ReportResult(
            title: 'Gross profit & net profit',
            description: 'Gross from sales invoices; net from GL to avoid double-counting.',
            columns: [
                ['key' => 'metric', 'label' => 'Metric', 'sortable' => false],
                ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
                ['key' => 'source', 'label' => 'Source ledger', 'sortable' => false],
            ],
            rows: $all,
            filters: $filters->toArray(),
            summary: ['gross_profit' => round($gross, 2), 'net_profit_gl' => $pnl['net_profit']],
            totalRows: count($all),
        );
    }

    public function generalLedger(ReportFilters $filters): ReportResult
    {
        if (! $filters->glAccountId) {
            return new ReportResult(
                title: 'General ledger',
                description: 'Select a GL account to load posted journal lines.',
                columns: [
                    ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                    ['key' => 'journal', 'label' => 'Journal', 'sortable' => true],
                    ['key' => 'debit', 'label' => 'Debit', 'sortable' => true],
                    ['key' => 'credit', 'label' => 'Credit', 'sortable' => true],
                    ['key' => 'memo', 'label' => 'Memo', 'sortable' => false],
                ],
                rows: [],
                filters: $filters->toArray(),
                totalRows: 0,
            );
        }

        $lines = $this->financial->generalLedger(
            $filters->glAccountId,
            $filters->from,
            $filters->to,
        );

        $account = GlAccount::query()->findOrFail($filters->glAccountId);

        $all = $lines->map(fn ($line) => [
            'date' => $line->entry?->entry_date?->toDateString(),
            'journal' => $line->entry?->entry_number,
            'debit' => round((float) $line->debit, 2),
            'credit' => round((float) $line->credit, 2),
            'memo' => $line->memo ?? $line->entry?->description ?? '',
        ])->all();

        return new ReportResult(
            title: 'General ledger — '.$account->code,
            description: 'Posted journal lines for selected account.',
            columns: [
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'journal', 'label' => 'Journal', 'sortable' => true],
                ['key' => 'debit', 'label' => 'Debit', 'sortable' => true],
                ['key' => 'credit', 'label' => 'Credit', 'sortable' => true],
                ['key' => 'memo', 'label' => 'Memo', 'sortable' => false],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    private function treasuryBook(string $type, ReportFilters $filters, string $title, string $description): ReportResult
    {
        $accounts = TreasuryAccount::query()->where('type', $type)->pluck('id');

        $rows = DB::table('treasury_movements as m')
            ->join('treasury_accounts as a', 'a.id', '=', 'm.treasury_account_id')
            ->whereIn('m.treasury_account_id', $accounts)
            ->when($filters->from, fn ($q) => $q->whereDate('m.movement_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('m.movement_date', '<=', $filters->to))
            ->selectRaw('m.movement_date, a.name as account, m.movement_type, m.amount, m.reference, m.notes')
            ->orderByDesc('m.movement_date')
            ->limit(2000)
            ->get();

        $all = $rows->map(fn ($r) => [
            'date' => (string) $r->movement_date,
            'account' => $r->account,
            'direction' => $r->movement_type,
            'amount' => round((float) $r->amount, 2),
            'reference' => $r->reference ?? '',
            'notes' => $r->notes ?? '',
        ])->all();

        return new ReportResult(
            title: $title,
            description: $description.' Cash movements — not profit.',
            columns: [
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'account', 'label' => 'Account', 'sortable' => true],
                ['key' => 'direction', 'label' => 'In/Out', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
                ['key' => 'reference', 'label' => 'Reference', 'sortable' => true],
                ['key' => 'notes', 'label' => 'Notes', 'sortable' => false],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }
}