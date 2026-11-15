<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Http\Requests\Hr\ProductionAssignmentRequest;
use App\Models\BagProductionBatch;
use App\Models\BagProductionOrder;
use App\Models\DanaBatch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeProductionAssignment;
use App\Models\Machine;
use App\Services\Hr\EmployeeProductionAssignmentService;
use App\Services\Hr\HrException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductionAssignmentController extends Controller
{
    public function __construct(
        private readonly EmployeeProductionAssignmentService $assignmentService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewModule', 'employees');

        $employeeId = $request->integer('employee_id') ?: null;
        $type = $request->string('assignment_type')->trim()->toString()
            ?: $request->string('type')->trim()->toString();

        $query = EmployeeProductionAssignment::query()->with([
            'employee',
            'department',
            'machine',
            'danaBatch',
            'bagProductionBatch',
            'bagProductionOrder',
        ]);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        if ($type !== '') {
            $query->where('assignment_type', $type);
        }

        return Inertia::render('Hr/ProductionAssignments/Index', [
            'pageTitle' => 'Production assignments',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Employees', 'href' => route('erp.employees')],
                ['label' => 'Assignments'],
            ],
            'assignments' => $query->orderByDesc('created_at')->paginate(20)->withQueryString(),
            'filters' => [
                'employee_id' => $employeeId,
                'assignment_type' => $type,
            ],
            'canManage' => $request->user()?->can('employees.manage') === true,
            'assignmentTypes' => config('hr.production_assignment_types'),
            'lookups' => [
                'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'employee_code', 'name']),
            ],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('manageEmployees');

        return Inertia::render('Hr/ProductionAssignments/Form', [
            'pageTitle' => 'New assignment',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Employees', 'href' => route('erp.employees')],
                ['label' => 'New assignment'],
            ],
            'assignment' => null,
            'lookups' => $this->lookups(),
        ]);
    }

    public function store(ProductionAssignmentRequest $request): RedirectResponse
    {
        $this->authorize('manageEmployees');

        try {
            $this->assignmentService->create($request->validated(), $request->user());
        } catch (HrException $e) {
            return back()->withErrors(['assignment_type' => $e->getMessage()])->withInput();
        }

        return redirect()->route('hr.production-assignments.index')->with('success', 'Assignment created.');
    }

    public function edit(EmployeeProductionAssignment $production_assignment): Response
    {
        $this->authorize('manageEmployees');

        $production_assignment->load('employee');

        return Inertia::render('Hr/ProductionAssignments/Form', [
            'pageTitle' => 'Edit assignment',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Employees', 'href' => route('erp.employees')],
                ['label' => 'Edit assignment'],
            ],
            'assignment' => $production_assignment,
            'lookups' => $this->lookups(),
        ]);
    }

    public function update(ProductionAssignmentRequest $request, EmployeeProductionAssignment $production_assignment): RedirectResponse
    {
        $this->authorize('manageEmployees');

        try {
            $this->assignmentService->update($production_assignment, $request->validated());
        } catch (HrException $e) {
            return back()->withErrors(['assignment_type' => $e->getMessage()])->withInput();
        }

        return redirect()->route('hr.production-assignments.index')->with('success', 'Assignment updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function lookups(): array
    {
        return [
            'employees' => Employee::query()->where('is_active', true)->orderBy('name')->get(['id', 'employee_code', 'name']),
            'departments' => Department::query()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']),
            'machines' => Machine::query()->where('status', 'active')->orderBy('code')->get(['id', 'code', 'name']),
            'danaBatches' => DanaBatch::query()->orderByDesc('id')->limit(100)->get(['id', 'batch_number', 'production_date']),
            'bagBatches' => BagProductionBatch::query()->orderByDesc('id')->limit(100)->get(['id', 'batch_number', 'production_date']),
            'bagOrders' => BagProductionOrder::query()->orderByDesc('id')->limit(100)->get(['id', 'order_number']),
            'assignmentTypes' => config('hr.production_assignment_types'),
        ];
    }
}