<?php

namespace App\Http\Controllers\Expenses;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\TreasuryAccount;
use App\Services\Accounting\AccountingException;
use App\Services\Accounting\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $expenses)
    {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'expenses');

        $rows = Expense::query()->with('category')->orderByDesc('expense_date')->paginate(15);

        return Inertia::render('Expenses/Index', [
            'pageTitle' => 'Expenses',
            'breadcrumbs' => $this->crumbs('Expenses'),
            'expenses' => $rows,
            'canManage' => $request->user()?->can('expenses.manage') === true,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('manageExpenses');

        return Inertia::render('Expenses/Form', [
            'pageTitle' => 'New expense',
            'breadcrumbs' => $this->crumbs('New expense'),
            'categories' => ExpenseCategory::query()->where('status', 'active')->orderBy('name')->get(),
            'treasuryAccounts' => TreasuryAccount::query()->where('status', 'active')->orderBy('name')->get(),
            'paymentStatuses' => config('accounting.expense_payment_statuses'),
            'paymentMethods' => config('accounting.payment_methods'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manageExpenses');

        $data = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_status' => ['required', 'in:paid,unpaid'],
            'payment_method' => ['nullable', 'string'],
            'treasury_account_id' => ['nullable', 'exists:treasury_accounts,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->expenses->create($data, $request->user());
        } catch (AccountingException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('expenses.entries.index')->with('status', 'Expense recorded and posted.');
    }

    /**
     * @return list<array{label: string, href?: string}>
     */
    private function crumbs(string $tail): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Expenses', 'href' => route('erp.expenses')],
            ['label' => $tail],
        ];
    }
}