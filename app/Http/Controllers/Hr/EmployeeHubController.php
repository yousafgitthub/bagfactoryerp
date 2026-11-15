<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'employees');

        return Inertia::render('Hr/Employees/Hub', [
            'pageTitle' => 'Employees',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Employees'],
            ],
            'links' => [
                [
                    'label' => 'Employee directory',
                    'description' => 'Search, filter, and maintain employee profiles with salary history.',
                    'href' => route('hr.employees.index'),
                ],
                [
                    'label' => 'Production assignments',
                    'description' => 'Link staff to machines, batches, orders, and departments.',
                    'href' => route('hr.production-assignments.index'),
                ],
                [
                    'label' => 'Department roster',
                    'description' => 'Active employees grouped by department.',
                    'href' => route('hr.reports.department-employees'),
                ],
            ],
            'canManage' => $request->user()?->can('employees.manage') === true,
        ]);
    }
}