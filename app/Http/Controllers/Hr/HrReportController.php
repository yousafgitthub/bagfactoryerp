<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Employee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HrReportController extends Controller
{
    public function departmentEmployees(Request $request): Response
    {
        $this->authorize('viewModule', 'employees');

        $departmentId = $request->integer('department_id') ?: null;

        $departments = Department::query()
            ->where('status', 'active')
            ->with(['designations' => fn ($q) => $q->where('status', 'active')])
            ->orderBy('name')
            ->get();

        $employeeQuery = Employee::query()
            ->with('designation')
            ->where('is_active', true);

        if ($departmentId) {
            $employeeQuery->where('department_id', $departmentId);
        }

        $employees = $employeeQuery->orderBy('department_id')->orderBy('name')->get();

        $grouped = $employees->groupBy(fn (Employee $e) => $e->department_id ?? 0);

        return Inertia::render('Hr/Reports/DepartmentEmployees', [
            'pageTitle' => 'Department roster',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Employees', 'href' => route('erp.employees')],
                ['label' => 'Department roster'],
            ],
            'departments' => $departments,
            'grouped' => $grouped,
            'departmentLabels' => $departments->pluck('name', 'id'),
            'selectedDepartmentId' => $departmentId,
        ]);
    }
}