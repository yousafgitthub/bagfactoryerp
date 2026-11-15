<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollHubController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $this->authorize('viewModule', 'payroll');

        return Inertia::render('Hr/Payroll/Hub', [
            'pageTitle' => 'Payroll',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Payroll'],
            ],
            'links' => [
                [
                    'label' => 'Payroll runs',
                    'description' => 'Generate monthly payroll from approved attendance and overtime.',
                    'href' => route('hr.payroll.runs.index'),
                ],
                [
                    'label' => 'Salary advances',
                    'description' => 'Track advances and automatic payroll recovery.',
                    'href' => route('hr.payroll.advances.index'),
                ],
                [
                    'label' => 'Employee loans',
                    'description' => 'Installment loans separate from advances.',
                    'href' => route('hr.payroll.loans.index'),
                ],
                [
                    'label' => 'Outstanding salary',
                    'description' => 'Approved payroll with unpaid balances.',
                    'href' => route('hr.payroll.reports.outstanding'),
                ],
            ],
            'canManage' => $request->user()?->can('payroll.manage') === true,
            'canApprove' => $request->user()?->can('payroll.approve') === true,
        ]);
    }
}