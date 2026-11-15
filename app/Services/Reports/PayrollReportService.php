<?php

namespace App\Services\Reports;

use App\Models\Attendance;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\OvertimeRecord;
use App\Models\PayrollRecord;
use App\Services\Reports\Concerns\PaginatesReportRows;
use Illuminate\Support\Facades\DB;

class PayrollReportService
{
    use PaginatesReportRows;

    public function monthlyPayroll(ReportFilters $filters): ReportResult
    {
        $query = PayrollRecord::query()
            ->with('employee:id,employee_code,name')
            ->join('payroll_runs as r', 'r.id', '=', 'payroll_records.payroll_run_id')
            ->whereIn('r.status', ['approved', 'paid', 'partially_paid'])
            ->when($filters->from, fn ($q) => $q->whereDate('payroll_records.period_end', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('payroll_records.period_start', '<=', $filters->to))
            ->select('payroll_records.*')
            ->orderByDesc('payroll_records.period_end');

        $all = $query->get()->map(fn ($rec) => [
            'employee' => $rec->employee?->employee_code.' '.$rec->employee?->name,
            'period' => $rec->period_start?->toDateString().' – '.$rec->period_end?->toDateString(),
            'gross' => round((float) $rec->gross_amount, 2),
            'deductions' => round((float) $rec->deductions_total, 2),
            'net' => round((float) $rec->net_payable, 2),
            'paid' => round((float) $rec->amount_paid, 2),
            'outstanding' => round((float) $rec->outstanding_amount, 2),
            'status' => $rec->status,
        ])->all();

        return new ReportResult(
            title: 'Monthly payroll',
            description: 'Payroll records from approved payroll runs.',
            columns: [
                ['key' => 'employee', 'label' => 'Employee', 'sortable' => true],
                ['key' => 'period', 'label' => 'Period', 'sortable' => true],
                ['key' => 'gross', 'label' => 'Gross', 'sortable' => true],
                ['key' => 'deductions', 'label' => 'Deductions', 'sortable' => true],
                ['key' => 'net', 'label' => 'Net payable', 'sortable' => true],
                ['key' => 'paid', 'label' => 'Paid', 'sortable' => true],
                ['key' => 'outstanding', 'label' => 'Outstanding', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: [
                'net_payable' => round(array_sum(array_column($all, 'net')), 2),
                'outstanding' => round(array_sum(array_column($all, 'outstanding')), 2),
            ],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function dailyWage(ReportFilters $filters): ReportResult
    {
        $query = PayrollRecord::query()
            ->with('employee:id,employee_code,name')
            ->where('salary_type', 'daily')
            ->when($filters->from, fn ($q) => $q->whereDate('period_end', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('period_start', '<=', $filters->to));

        $all = $query->get()->map(fn ($rec) => [
            'employee' => $rec->employee?->employee_code.' '.$rec->employee?->name,
            'payable_days' => (float) $rec->payable_days,
            'daily_rate' => round((float) $rec->daily_wage_rate, 2),
            'gross' => round((float) $rec->gross_amount, 2),
            'net' => round((float) $rec->net_payable, 2),
        ])->all();

        return new ReportResult(
            title: 'Daily wage report',
            description: 'Payroll lines for daily-rated employees.',
            columns: [
                ['key' => 'employee', 'label' => 'Employee', 'sortable' => true],
                ['key' => 'payable_days', 'label' => 'Payable days', 'sortable' => true],
                ['key' => 'daily_rate', 'label' => 'Daily rate', 'sortable' => true],
                ['key' => 'gross', 'label' => 'Gross', 'sortable' => true],
                ['key' => 'net', 'label' => 'Net', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function attendance(ReportFilters $filters): ReportResult
    {
        $query = Attendance::query()
            ->with('employee:id,employee_code,name')
            ->when($filters->from, fn ($q) => $q->whereDate('attendance_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('attendance_date', '<=', $filters->to))
            ->when($filters->employeeId, fn ($q) => $q->where('employee_id', $filters->employeeId))
            ->orderByDesc('attendance_date')
            ->limit(2000);

        $all = $query->get()->map(fn ($a) => [
            'date' => $a->attendance_date?->toDateString(),
            'employee' => $a->employee?->employee_code.' '.$a->employee?->name,
            'status' => $a->status,
            'check_in' => $a->check_in_at?->format('H:i') ?? '',
            'check_out' => $a->check_out_at?->format('H:i') ?? '',
            'hours' => $a->working_hours !== null ? round((float) $a->working_hours, 2) : '',
        ])->all();

        return new ReportResult(
            title: 'Attendance',
            description: 'Attendance records by date.',
            columns: [
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'employee', 'label' => 'Employee', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status', 'sortable' => true],
                ['key' => 'check_in', 'label' => 'In', 'sortable' => false],
                ['key' => 'check_out', 'label' => 'Out', 'sortable' => false],
                ['key' => 'hours', 'label' => 'Hours', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function overtime(ReportFilters $filters): ReportResult
    {
        $query = OvertimeRecord::query()
            ->with('employee:id,employee_code,name')
            ->when($filters->from, fn ($q) => $q->whereDate('overtime_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('overtime_date', '<=', $filters->to))
            ->orderByDesc('overtime_date');

        $all = $query->get()->map(fn ($o) => [
            'date' => $o->overtime_date?->toDateString(),
            'employee' => $o->employee?->employee_code.' '.$o->employee?->name,
            'hours' => round((float) $o->hours, 2),
            'rate' => round((float) $o->rate_per_hour, 2),
            'amount' => round((float) $o->calculated_amount, 2),
            'status' => $o->approval_status,
        ])->all();

        return new ReportResult(
            title: 'Overtime',
            description: 'Approved and pending overtime records.',
            columns: [
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'employee', 'label' => 'Employee', 'sortable' => true],
                ['key' => 'hours', 'label' => 'Hours', 'sortable' => true],
                ['key' => 'rate', 'label' => 'Rate', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Amount', 'sortable' => true],
                ['key' => 'status', 'label' => 'Approval', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            summary: ['overtime_total' => round(array_sum(array_column($all, 'amount')), 2)],
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function salaryPaidOutstanding(ReportFilters $filters): ReportResult
    {
        $totals = PayrollRecord::query()
            ->when($filters->from, fn ($q) => $q->whereDate('period_end', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('period_start', '<=', $filters->to))
            ->selectRaw('status')
            ->selectRaw('SUM(net_payable) as net')
            ->selectRaw('SUM(amount_paid) as paid')
            ->selectRaw('SUM(outstanding_amount) as outstanding')
            ->groupBy('status')
            ->get();

        $all = $totals->map(fn ($r) => [
            'status' => $r->status,
            'net_payable' => round((float) $r->net, 2),
            'paid' => round((float) $r->paid, 2),
            'outstanding' => round((float) $r->outstanding, 2),
        ])->all();

        return new ReportResult(
            title: 'Salary paid & outstanding',
            description: 'Reconciles payroll_records amounts (not duplicate GL payroll expense).',
            columns: [
                ['key' => 'status', 'label' => 'Record status', 'sortable' => true],
                ['key' => 'net_payable', 'label' => 'Net payable', 'sortable' => true],
                ['key' => 'paid', 'label' => 'Paid', 'sortable' => true],
                ['key' => 'outstanding', 'label' => 'Outstanding', 'sortable' => true],
            ],
            rows: $all,
            filters: $filters->toArray(),
            totalRows: count($all),
        );
    }

    public function advances(ReportFilters $filters): ReportResult
    {
        $all = EmployeeAdvance::query()
            ->with('employee:id,employee_code,name')
            ->when($filters->from, fn ($q) => $q->whereDate('advance_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('advance_date', '<=', $filters->to))
            ->orderByDesc('advance_date')
            ->get()
            ->map(fn ($a) => [
                'date' => $a->advance_date?->toDateString(),
                'employee' => $a->employee?->employee_code.' '.$a->employee?->name,
                'amount' => round((float) $a->amount, 2),
                'recovered' => round((float) $a->amount_repaid, 2),
                'outstanding' => round((float) $a->outstanding_balance, 2),
                'status' => $a->status,
            ])->all();

        return new ReportResult(
            title: 'Employee advances',
            description: 'Advance balances from HR ledger.',
            columns: [
                ['key' => 'date', 'label' => 'Date', 'sortable' => true],
                ['key' => 'employee', 'label' => 'Employee', 'sortable' => true],
                ['key' => 'amount', 'label' => 'Advanced', 'sortable' => true],
                ['key' => 'recovered', 'label' => 'Recovered', 'sortable' => true],
                ['key' => 'outstanding', 'label' => 'Outstanding', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }

    public function loans(ReportFilters $filters): ReportResult
    {
        $all = EmployeeLoan::query()
            ->with('employee:id,employee_code,name')
            ->when($filters->from, fn ($q) => $q->whereDate('loan_date', '>=', $filters->from))
            ->when($filters->to, fn ($q) => $q->whereDate('loan_date', '<=', $filters->to))
            ->orderByDesc('loan_date')
            ->get()
            ->map(fn ($l) => [
                'date' => $l->loan_date?->toDateString(),
                'employee' => $l->employee?->employee_code.' '.$l->employee?->name,
                'principal' => round((float) $l->principal, 2),
                'repaid' => round((float) $l->amount_repaid, 2),
                'outstanding' => round((float) $l->outstanding_principal, 2),
                'status' => $l->status,
            ])->all();

        return new ReportResult(
            title: 'Employee loans & repayments',
            description: 'Loan principal and outstanding from employee_loans.',
            columns: [
                ['key' => 'date', 'label' => 'Loan date', 'sortable' => true],
                ['key' => 'employee', 'label' => 'Employee', 'sortable' => true],
                ['key' => 'principal', 'label' => 'Principal', 'sortable' => true],
                ['key' => 'repaid', 'label' => 'Repaid', 'sortable' => true],
                ['key' => 'outstanding', 'label' => 'Outstanding', 'sortable' => true],
                ['key' => 'status', 'label' => 'Status', 'sortable' => true],
            ],
            rows: $this->paginateRows($all, $filters),
            filters: $filters->toArray(),
            totalRows: $this->totalRowCount($all, $filters),
        );
    }
}