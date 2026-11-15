<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\PayrollRecord;
use App\Services\Hr\PayrollCalculationService;
use App\Services\Hr\PayrollException;
use App\Services\Hr\PayrollPaymentService;
use App\Services\Hr\PayrollRunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PayrollRecordController extends Controller
{
    public function __construct(
        private readonly PayrollRunService $payrollRuns,
        private readonly PayrollCalculationService $calculator,
        private readonly PayrollPaymentService $payments
    ) {}

    public function show(Request $request, PayrollRecord $payrollRecord): Response
    {
        $this->authorize('viewModule', 'payroll');

        $payrollRecord->load(['employee.department', 'lines', 'payments.recorder', 'run']);

        return Inertia::render('Hr/Payroll/Records/Show', [
            'pageTitle' => 'Payroll — '.$payrollRecord->employee->name,
            'breadcrumbs' => [
                ['label' => 'Dashboard', 'href' => route('dashboard')],
                ['label' => 'Payroll', 'href' => route('erp.payroll')],
                ['label' => $payrollRecord->run->period_label, 'href' => route('hr.payroll.runs.show', $payrollRecord->payroll_run_id)],
                ['label' => $payrollRecord->employee->employee_code],
            ],
            'record' => $payrollRecord,
            'lineTypes' => config('payroll.line_types'),
            'paymentMethods' => config('payroll.payment_methods'),
            'canManage' => $request->user()?->can('payroll.manage') === true,
            'canApprove' => $request->user()?->can('payroll.approve') === true,
        ]);
    }

    public function payslip(PayrollRecord $payrollRecord): Response
    {
        $this->authorize('viewModule', 'payroll');

        $payrollRecord->load(['employee.department', 'lines', 'payments', 'run']);

        return Inertia::render('Hr/Payroll/Records/Payslip', [
            'pageTitle' => 'Salary slip',
            'record' => $payrollRecord,
            'lineTypes' => config('payroll.line_types'),
        ]);
    }

    public function recalculate(PayrollRecord $payrollRecord): RedirectResponse
    {
        $this->authorize('managePayroll');

        try {
            $this->payrollRuns->recalculateRecord($payrollRecord);
        } catch (PayrollException $e) {
            return back()->withErrors(['recalculate' => $e->getMessage()]);
        }

        return back()->with('status', 'Employee payroll recalculated.');
    }

    public function updateLines(Request $request, PayrollRecord $payrollRecord): RedirectResponse
    {
        $this->authorize('managePayroll');

        if (! in_array($payrollRecord->status, ['draft', 'reviewed'], true)) {
            return back()->withErrors(['lines' => 'Bonuses and deductions can only be edited before approval.']);
        }

        $validated = $request->validate([
            'lines' => ['nullable', 'array'],
            'lines.*.line_type' => ['required_with:lines', 'in:bonus,allowance,other_deduction'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.amount' => ['required_with:lines', 'numeric', 'min:0'],
        ]);

        $payrollRecord->lines()
            ->whereIn('line_type', ['bonus', 'allowance', 'other_deduction'])
            ->delete();

        $this->calculator->applyManualLines($payrollRecord, $validated['lines'] ?? []);
        $paid = (float) $payrollRecord->amount_paid;
        $payrollRecord->update([
            'outstanding_amount' => max(0, round((float) $payrollRecord->fresh()->net_payable - $paid, 2)),
        ]);
        $this->payrollRuns->syncRunTotals($payrollRecord->run);

        return back()->with('status', 'Manual payroll lines saved.');
    }

    public function storePayment(Request $request, PayrollRecord $payrollRecord): RedirectResponse
    {
        $this->authorize('managePayroll');

        $validated = $request->validate([
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', 'in:'.implode(',', array_keys(config('payroll.payment_methods')))],
            'reference' => ['nullable', 'string', 'max:128'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->payments->recordPayment($payrollRecord, $validated, $request->user());
        } catch (PayrollException $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }

        return back()->with('status', 'Salary payment recorded.');
    }
}