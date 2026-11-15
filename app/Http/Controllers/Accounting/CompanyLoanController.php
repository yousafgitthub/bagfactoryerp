<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\CompanyLoan;
use App\Models\TreasuryAccount;
use App\Services\Accounting\AccountingException;
use App\Services\Accounting\CompanyLoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyLoanController extends Controller
{
    public function __construct(private readonly CompanyLoanService $loans)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'accounting');

        $loans = CompanyLoan::query()->orderByDesc('start_date')->paginate(15);

        return Inertia::render('Accounting/CompanyLoans/Index', [
            'pageTitle' => 'Company loans',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Expenses', 'href' => route('erp.expenses')],
                ['label' => 'Company loans'],
            ],
            'loans' => $loans,
            'canManage' => $request->user()?->can('accounting.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageAccounting');

        return Inertia::render('Accounting/CompanyLoans/Form', [
            'pageTitle' => 'New company loan',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Expenses', 'href' => route('erp.expenses')],
                ['label' => 'Company loans', 'href' => route('accounting.company-loans.index')],
                ['label' => 'New'],
            ],
            'directions' => config('accounting.loan_directions'),
            'treasuryAccounts' => TreasuryAccount::query()->where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageAccounting');

        $data = $request->validate([
            'direction' => ['required', 'in:received,given'],
            'counterparty_name' => ['required', 'string', 'max:255'],
            'principal' => ['required', 'numeric', 'min:0.01'],
            'start_date' => ['required', 'date'],
            'maturity_date' => ['nullable', 'date'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'treasury_account_id' => ['required', 'exists:treasury_accounts,id'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $this->loans->create($data, $request->user());
        } catch (AccountingException $e) {
            return back()->withErrors(['principal' => $e->getMessage()]);
        }

        return redirect()->route('accounting.company-loans.index')->with('status', 'Loan recorded.');
    }

    public function storeRepayment(Request $request, CompanyLoan $companyLoan): RedirectResponse
    {
        $this->authorize('manageAccounting');

        $data = $request->validate([
            'repayment_date' => ['required', 'date'],
            'principal_amount' => ['required', 'numeric', 'min:0'],
            'interest_amount' => ['nullable', 'numeric', 'min:0'],
            'treasury_account_id' => ['required', 'exists:treasury_accounts,id'],
        ]);

        try {
            $this->loans->recordRepayment($companyLoan, $data, $request->user());
        } catch (AccountingException $e) {
            return back()->withErrors(['principal_amount' => $e->getMessage()]);
        }

        return back()->with('status', 'Repayment posted.');
    }
}