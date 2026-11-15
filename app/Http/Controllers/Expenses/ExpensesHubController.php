<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpensesHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'expenses');

        return Inertia::render('Expenses/Hub', [
            'pageTitle' => 'Expenses',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Expenses'],
            ],
            'links' => [
                [
                    'label' => 'Expense entries',
                    'description' => 'Factory operating expenses with paid or unpaid bills.',
                    'href' => route('expenses.entries.index'),
                ],
                [
                    'label' => 'Treasury / cashbook',
                    'description' => 'Cash and bank accounts, transfers and balances.',
                    'href' => route('accounting.treasury.index'),
                ],
                [
                    'label' => 'Company loans',
                    'description' => 'Loans received or given with principal and interest repayments.',
                    'href' => route('accounting.company-loans.index'),
                ],
            ],
            'canManage' => $request->user()?->can('expenses.manage') === true,
        ]);
    }
}