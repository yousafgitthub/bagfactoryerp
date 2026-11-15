<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\GlAccount;
use App\Services\Accounting\FinancialReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FinancialReportController extends Controller
{
    public function __construct(private readonly FinancialReportService $reports)
    {
    }

    public function trialBalance(Request $request): Response
    {
        $this->authorize('viewModule', 'accounting');

        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'pageTitle' => 'Trial balance',
            'breadcrumbs' => $this->crumbs('Trial balance'),
            'filters' => ['from' => $from, 'to' => $to],
            'rows' => $this->reports->trialBalance($from, $to),
        ]);
    }

    public function profitAndLoss(Request $request): Response
    {
        $this->authorize('viewModule', 'accounting');

        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;
        $report = $this->reports->profitAndLoss($from, $to);

        return Inertia::render('Accounting/Reports/ProfitLoss', [
            'pageTitle' => 'Profit & loss',
            'breadcrumbs' => $this->crumbs('Profit & loss'),
            'filters' => ['from' => $from, 'to' => $to],
            'report' => $report,
        ]);
    }

    public function balanceSheet(Request $request): Response
    {
        $this->authorize('viewModule', 'accounting');

        $asOf = $request->string('as_of')->toString() ?: null;
        $report = $this->reports->balanceSheet($asOf);

        return Inertia::render('Accounting/Reports/BalanceSheet', [
            'pageTitle' => 'Balance sheet',
            'breadcrumbs' => $this->crumbs('Balance sheet'),
            'filters' => ['as_of' => $asOf],
            'report' => $report,
        ]);
    }

    public function ledger(Request $request): Response
    {
        $this->authorize('viewModule', 'accounting');

        $accountId = $request->integer('gl_account_id');
        $from = $request->string('from')->toString() ?: null;
        $to = $request->string('to')->toString() ?: null;

        $lines = $accountId
            ? $this->reports->generalLedger($accountId, $from, $to)
            : collect();

        return Inertia::render('Accounting/Reports/Ledger', [
            'pageTitle' => 'General ledger',
            'breadcrumbs' => $this->crumbs('General ledger'),
            'accounts' => GlAccount::query()->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name']),
            'filters' => ['gl_account_id' => $accountId ?: null, 'from' => $from, 'to' => $to],
            'lines' => $lines,
        ]);
    }

    /**
     * @return list<array{label: string, href?: string}>
     */
    private function crumbs(string $tail): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Accounting', 'href' => route('erp.accounting')],
            ['label' => $tail],
        ];
    }
}