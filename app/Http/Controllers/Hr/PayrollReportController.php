<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollReportController extends Controller
{
    public function outstanding(Request $request): Response
    {
        $this->authorize('viewModule', 'payroll');

        $records = PayrollRecord::query()
            ->with(['employee', 'run'])
            ->whereIn('status', ['approved', 'partially_paid'])
            ->where('outstanding_amount', '>', 0)
            ->orderByDesc('period_end')
            ->paginate(50);

        $totalOutstanding = PayrollRecord::query()
            ->whereIn('status', ['approved', 'partially_paid'])
            ->sum('outstanding_amount');

        return Inertia::render('Hr/Payroll/Reports/Outstanding', [
            'pageTitle' => 'Outstanding salary',
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Payroll', 'href' => route('erp.payroll')],
                ['label' => 'Outstanding'],
            ],
            'records' => $records,
            'totalOutstanding' => round((float) $totalOutstanding, 2),
        ]);
    }
}