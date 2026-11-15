<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Services\Hr\AdvanceLoanService;
use App\Services\Hr\PayrollException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeAdvanceController extends Controller
{
    public function __construct(
        private readonly AdvanceLoanService $advanceLoans
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'payroll');

        $employeeId = $request->integer('employee_id') ?: null;
        $status = $request->string('status')->trim()->toString();

        $query = EmployeeAdvance::query()->with('employee')->orderByDesc('advance_date');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        if ($status !== '') {
            $query->where('status', $status);
        }

        return Inertia::render('Hr/Payroll/Advances/Index', [
            'pageTitle' => 'Salary advances',
            'breadcrumbs' => $this->breadcrumbs(),
            'advances' => $query->paginate(25)->withQueryString(),
            'filters' => ['employee_id' => $employeeId, 'status' => $status],
            'lookups' => [
                'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'employee_code', 'name']),
                'statuses' => config('payroll.advance_statuses'),
            ],
            'canManage' => $request->user()?->can('payroll.manage') === true,
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('managePayroll');

        return Inertia::render('Hr/Payroll/Advances/Form', [
            'pageTitle' => 'Record advance',
            'breadcrumbs' => $this->breadcrumbs('New'),
            'advance' => null,
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
            'advance_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
            'per_payroll_deduction' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $employee = Employee::query()->findOrFail($validated['employee_id']);

        try {
            $this->advanceLoans->recordAdvance($employee, $validated, $request->user());
        } catch (PayrollException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }

        return redirect()->route('hr.payroll.advances.index')->with('status', 'Advance recorded.');
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function breadcrumbs(string $trail = 'Advances'): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Payroll', 'href' => route('erp.payroll')],
            ['label' => $trail],
        ];
    }
}