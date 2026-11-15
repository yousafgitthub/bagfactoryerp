<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountingHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'accounting');

        return Inertia::render('Accounting/Hub', [
            'pageTitle' => 'Accounting',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Accounting'],
            ],
            'links' => [
                ['label' => 'Chart of accounts', 'description' => 'GL account structure and posting flags.', 'href' => route('accounting.gl-accounts.index')],
                ['label' => 'Journal entries', 'description' => 'Posted double-entry journals (read-only).', 'href' => route('accounting.journals.index')],
                ['label' => 'General ledger', 'description' => 'Account activity and running balances.', 'href' => route('accounting.reports.ledger')],
                ['label' => 'Trial balance', 'description' => 'Debit/credit totals by account.', 'href' => route('accounting.reports.trial-balance')],
                ['label' => 'Profit & loss', 'description' => 'Revenue and expense for a period.', 'href' => route('accounting.reports.profit-loss')],
                ['label' => 'Balance sheet', 'description' => 'Assets, liabilities and equity.', 'href' => route('accounting.reports.balance-sheet')],
            ],
            'canManage' => $request->user()?->can('accounting.manage') === true,
        ]);
    }
}