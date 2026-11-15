<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\EmployeeRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Services\Hr\EmployeePersistenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly EmployeePersistenceService $persistence
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'employees');

        $search = $request->string('search')->trim()->toString();
        $departmentId = $request->integer('department_id') ?: null;
        $status = $request->string('status')->trim()->toString();

        $query = Employee::query()->with(['department', 'designation']);

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('employee_code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        if ($status !== '') {
            $query->where('status', $status);
        }

        return Inertia::render('Hr/Employees/Index', [
            'pageTitle' => 'Employees',
            'breadcrumbs' => $this->employeeBreadcrumbs('Directory'),
            'employees' => $query->orderBy('employee_code')->paginate(20)->withQueryString(),
            'filters' => [
                'search' => $search,
                'department_id' => $departmentId,
                'status' => $status,
            ],
            'lookups' => [
                'departments' => Department::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']),
                'statuses' => config('hr.employee_statuses'),
            ],
            'canManage' => $request->user()?->can('employees.manage') === true,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageEmployees');

        return Inertia::render('Hr/Employees/Form', [
            'pageTitle' => 'New employee',
            'breadcrumbs' => $this->employeeBreadcrumbs('New employee'),
            'employee' => null,
            'lookups' => $this->lookups(),
        ]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $this->authorize('manageEmployees');

        $employee = $this->persistence->create($request->validated(), $request->user());

        return redirect()->route('hr.employees.show', $employee)->with('success', 'Employee created.');
    }

    public function show(Request $request, Employee $employee): Response
    {
        $this->authorize('viewModule', 'employees');

        $employee->load([
            'department',
            'designation',
            'salaryRates.creator',
            'productionAssignments.machine',
            'productionAssignments.department',
        ]);

        return Inertia::render('Hr/Employees/Show', [
            'pageTitle' => $employee->name,
            'breadcrumbs' => $this->employeeBreadcrumbs($employee->name),
            'employee' => $employee,
            'canManage' => $request->user()?->can('employees.manage') === true,
            'salaryTypes' => config('hr.salary_types'),
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $this->authorize('manageEmployees');

        $employee->load(['department', 'designation']);

        return Inertia::render('Hr/Employees/Form', [
            'pageTitle' => 'Edit employee',
            'breadcrumbs' => $this->employeeBreadcrumbs('Edit'),
            'employee' => $employee,
            'lookups' => $this->lookups(),
        ]);
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->authorize('manageEmployees');

        $this->persistence->update($employee, $request->validated(), $request->user());

        return redirect()->route('hr.employees.show', $employee)->with('success', 'Employee updated.');
    }

    /**
     * @return array<int, array{label: string, href?: string}>
     */
    private function employeeBreadcrumbs(string $leaf): array
    {
        return [
            ['label' => 'Dashboard', 'href' => route('dashboard')],
            ['label' => 'Employees', 'href' => route('erp.employees')],
            ['label' => $leaf],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'departments' => Department::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']),
            'designations' => Designation::query()->where('status', 'active')->with('department:id,name')->orderBy('name')->get(['id', 'name', 'code', 'department_id']),
            'salaryTypes' => config('hr.salary_types'),
            'statuses' => config('hr.employee_statuses'),
        ];
    }
}