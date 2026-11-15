<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Services\Hr\AdvanceLoanService;
use App\Services\Hr\PayrollException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeLoanController extends Controller
{
    public function __construct(
        private readonly AdvanceLoanService $advanceLoans
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'payroll');

        $employeeId = $request->integer('employee_id') ?: null;
        $status = $request->string('status')->trim()->toString();

        $query = EmployeeLoan::query()->with('employee')->orderByDesc('loan_date');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        return Inertia::render('Hr/Payroll/Loans/Index', [
            'pageTitle' => 'Employee loans',
            'breadcrumbs' => $this->breadcrumbs(),
            'loans' => $query->paginate(25)->withQueryString(),
            'filters' => ['employee_id' => $employeeId, 'status' => $status],
            'lookups' => [
                'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'employee_code', 'name']),
                'statuses' => config('payroll.loan_statuses'),
            ],
            'canManage' => $request->user()?->can('payroll.manage') === true,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('managePayroll');

        return Inertia::render('Hr/Payroll/Loans/Form', [
            'pageTitle' => 'Record loan',
            'breadcrumbs' => $this->breadcrumbs('New'),
            'loan' => null,
            'lookups' => [
                'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'employee_code', 'name']),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('managePayroll');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'loan_date' => ['required', 'date'],
            'principal' => ['required', 'numeric', 'min:0.01'],
            'installment_amount' => ['required', 'numeric', 'min:0.01'],
            'installment_count' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $employee = Employee::query()->findOrFail($validated['employee_id']);

        try {
            $this->advanceLoans->recordLoan($employee, $validated, $request->user());
        } catch (PayrollException $e) {
            return back()->withErrors(['principal' => $e->getMessage()])->withInput();
        }

        return redirect()->route('hr.payroll.loans.index')->with('status', 'Loan recorded.');
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function breadcrumbs(string $trail = 'Loans'): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Payroll', 'href' => route('erp.payroll')],
            ['label' => $trail],
        ];
    }
}