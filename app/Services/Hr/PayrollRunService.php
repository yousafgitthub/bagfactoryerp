<?php

namespace App\Services\Hr;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\PayrollRecord;
use App\Models\PayrollRecordLine;
use App\Models\PayrollRun;
use App\Models\User;
use Carbon\Carbon;
use App\Services\Accounting\AccountingIntegrationService;
use Illuminate\Support\Facades\DB;

class PayrollRunService
{
    public function __construct(
        private readonly PayrollCalculationService $calculator,
        private readonly AccountingIntegrationService $accounting,
    ) {}

    public function createRun(Carbon $periodStart, Carbon $periodEnd, ?User $actor = null, ?string $notes = null): PayrollRun
    {
        if ($periodEnd->lt($periodStart)) {
            throw new PayrollException('Period end must be on or after period start.');
        }

        $label = $periodStart->format('M Y');

        return DB::transaction(function () use ($periodStart, $periodEnd, $label, $actor, $notes) {
            if (PayrollRun::query()->where('period_start', $periodStart)->where('period_end', $periodEnd)->exists()) {
                throw new PayrollException('A payroll run already exists for this period.');
            }

            return PayrollRun::query()->create([
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
                'period_label' => $label,
                'status' => 'draft',
                'notes' => $notes,
                'created_by' => $actor?->id,
            ]);
        });
    }

    public function generate(PayrollRun $run, ?User $actor = null): PayrollRun
    {
        if (! in_array($run->status, ['draft', 'reviewed'], true)) {
            throw new PayrollException('Payroll can only be generated while the run is in draft or reviewed status.');
        }

        return DB::transaction(function () use ($run) {
            $employees = Employee::query()->where('is_active', true)->orderBy('name')->get();

            foreach ($employees as $employee) {
                $this->assertNoDuplicatePeriodRecord($employee, $run);
                $this->upsertEmployeeRecord($employee, $run);
            }

            $run->update([
                'generated_at' => now(),
                'status' => 'draft',
            ]);

            return $this->syncRunTotals($run->fresh(['records']));
        });
    }

    public function recalculateRecord(PayrollRecord $record): PayrollRecord
    {
        if ($record->deductions_posted) {
            throw new PayrollException('Cannot recalculate a record after deductions have been posted.');
        }

        if (! in_array($record->status, ['draft', 'reviewed'], true)) {
            throw new PayrollException('Only draft or reviewed records can be recalculated.');
        }

        $run = $record->run;
        if (! in_array($run->status, ['draft', 'reviewed'], true)) {
            throw new PayrollException('The payroll run is no longer editable.');
        }

        return DB::transaction(function () use ($record, $run) {
            $employee = $record->employee;
            $manual = $record->lines()
                ->whereIn('line_type', ['bonus', 'allowance', 'other_deduction'])
                ->get()
                ->map(fn (PayrollRecordLine $line) => [
                    'line_type' => $line->line_type,
                    'description' => $line->description,
                    'amount' => abs((float) $line->amount),
                ])
                ->all();

            $record->lines()->delete();

            $calc = $this->calculator->buildCalculation($employee, $run);
            $this->applyCalculation($record, $calc);

            if ($manual !== []) {
                $this->calculator->applyManualLines($record, $manual);
            }

            $paid = (float) $record->amount_paid;
            $record->update([
                'outstanding_amount' => max(0, round((float) $record->fresh()->net_payable - $paid, 2)),
            ]);

            $this->syncRunTotals($run->fresh());

            return $record->fresh(['lines', 'employee']);
        });
    }

    public function review(PayrollRun $run, User $actor): PayrollRun
    {
        if ($run->status !== 'draft') {
            throw new PayrollException('Only draft payroll runs can be marked reviewed.');
        }

        if ($run->records()->count() === 0) {
            throw new PayrollException('Generate payroll lines before review.');
        }

        return DB::transaction(function () use ($run, $actor) {
            $run->update([
                'status' => 'reviewed',
                'reviewed_at' => now(),
                'reviewed_by' => $actor->id,
            ]);
            $run->records()->where('status', 'draft')->update(['status' => 'reviewed']);

            return $run->fresh();
        });
    }

    public function approve(PayrollRun $run, User $actor): PayrollRun
    {
        if (! in_array($run->status, ['draft', 'reviewed'], true)) {
            throw new PayrollException('This payroll run cannot be approved.');
        }

        return DB::transaction(function () use ($run, $actor) {
            $run->records()->each(function (PayrollRecord $record) {
                if ($record->deductions_posted) {
                    return;
                }
                $this->postLoanAndAdvanceDeductions($record);
                $record->update([
                    'status' => 'approved',
                    'deductions_posted' => true,
                ]);
            });

            $run->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => $actor->id,
            ]);

            $run = $this->syncRunTotals($run->fresh());
            $this->accounting->postPayrollAccrual($run, $actor);

            return $run;
        });
    }

    public function cancel(PayrollRun $run, User $actor, string $reason): PayrollRun
    {
        if (in_array($run->status, ['paid', 'cancelled'], true)) {
            throw new PayrollException('This payroll run cannot be cancelled.');
        }

        return DB::transaction(function () use ($run, $reason) {
            if ($run->status === 'approved' || $run->status === 'partially_paid') {
                $run->records()->each(fn (PayrollRecord $record) => $this->reverseDeductionPostings($record));
            }

            $run->update(['status' => 'cancelled', 'notes' => trim(($run->notes ?? '')."\nCancelled: ".$reason)]);
            $run->records()->update(['status' => 'voided']);

            return $run->fresh();
        });
    }

    private function upsertEmployeeRecord(Employee $employee, PayrollRun $run): PayrollRecord
    {
        $record = PayrollRecord::query()->firstOrNew([
            'payroll_run_id' => $run->id,
            'employee_id' => $employee->id,
        ]);

        if ($record->exists && $record->deductions_posted) {
            throw new PayrollException("Cannot regenerate payroll for {$employee->employee_code}; deductions already posted.");
        }

        $calc = $this->calculator->buildCalculation($employee, $run);

        $record->fill([
            'period_start' => $run->period_start,
            'period_end' => $run->period_end,
            'salary_type' => $calc['salary_type'],
            'basic_monthly_salary' => $calc['basic_monthly_salary'],
            'daily_wage_rate' => $calc['daily_wage_rate'],
            'employee_salary_rate_id' => $calc['employee_salary_rate_id'],
            'payable_days' => $calc['payable_days'],
            'absent_days' => $calc['absent_days'],
            'overtime_hours' => $calc['overtime_hours'],
            'overtime_amount' => $calc['overtime_amount'],
            'bonuses_total' => $calc['bonuses_total'],
            'allowances_total' => $calc['allowances_total'],
            'deductions_total' => $calc['deductions_total'],
            'gross_amount' => $calc['gross_amount'],
            'net_payable' => $calc['net_payable'],
            'outstanding_amount' => $calc['net_payable'],
            'status' => 'draft',
        ]);

        if (! $record->exists) {
            $record->amount_paid = 0;
        }

        $record->save();
        $record->lines()->delete();
        $this->applyCalculation($record, $calc);

        return $record;
    }

    /**
     * @param  array<string, mixed>  $calc
     */
    private function applyCalculation(PayrollRecord $record, array $calc): void
    {
        foreach ($calc['lines'] as $line) {
            PayrollRecordLine::query()->create([
                'payroll_record_id' => $record->id,
                'line_type' => $line['line_type'],
                'description' => $line['description'],
                'amount' => $line['amount'],
                'source_type' => $line['source_type'] ?? null,
                'source_id' => $line['source_id'] ?? null,
            ]);
        }
    }

    private function assertNoDuplicatePeriodRecord(Employee $employee, PayrollRun $run): void
    {
        $exists = PayrollRecord::query()
            ->where('employee_id', $employee->id)
            ->where('period_start', $run->period_start)
            ->where('period_end', $run->period_end)
            ->whereNotIn('status', ['voided'])
            ->where('payroll_run_id', '!=', $run->id)
            ->exists();

        if ($exists) {
            throw new PayrollException("Duplicate payroll period for {$employee->employee_code}.");
        }
    }

    private function postLoanAndAdvanceDeductions(PayrollRecord $record): void
    {
        $record->load('lines');

        foreach ($record->lines as $line) {
            if ($line->line_type === 'advance_deduction' && $line->source_type === EmployeeAdvance::class && $line->source_id) {
                $advance = EmployeeAdvance::query()->lockForUpdate()->find($line->source_id);
                if (! $advance || $advance->status !== 'open') {
                    continue;
                }
                $amount = min(abs((float) $line->amount), (float) $advance->outstanding_balance);
                if ($amount <= 0) {
                    continue;
                }
                $newRepaid = round((float) $advance->amount_repaid + $amount, 2);
                $newOutstanding = max(0, round((float) $advance->outstanding_balance - $amount, 2));
                $advance->update([
                    'amount_repaid' => $newRepaid,
                    'outstanding_balance' => $newOutstanding,
                    'status' => $newOutstanding <= 0 ? 'settled' : 'open',
                ]);
            }

            if ($line->line_type === 'loan_deduction' && $line->source_type === EmployeeLoan::class && $line->source_id) {
                $loan = EmployeeLoan::query()->lockForUpdate()->find($line->source_id);
                if (! $loan || $loan->status !== 'active') {
                    continue;
                }
                $amount = min(abs((float) $line->amount), (float) $loan->outstanding_principal);
                if ($amount <= 0) {
                    continue;
                }
                $newRepaid = round((float) $loan->amount_repaid + $amount, 2);
                $newOutstanding = max(0, round((float) $loan->outstanding_principal - $amount, 2));
                $loan->update([
                    'amount_repaid' => $newRepaid,
                    'outstanding_principal' => $newOutstanding,
                    'status' => $newOutstanding <= 0 ? 'closed' : 'active',
                ]);
            }
        }
    }

    private function reverseDeductionPostings(PayrollRecord $record): void
    {
        if (! $record->deductions_posted) {
            return;
        }

        $record->load('lines');

        foreach ($record->lines as $line) {
            if ($line->line_type === 'advance_deduction' && $line->source_type === EmployeeAdvance::class && $line->source_id) {
                $advance = EmployeeAdvance::query()->lockForUpdate()->find($line->source_id);
                if (! $advance) {
                    continue;
                }
                $amount = abs((float) $line->amount);
                $advance->update([
                    'amount_repaid' => max(0, round((float) $advance->amount_repaid - $amount, 2)),
                    'outstanding_balance' => round((float) $advance->outstanding_balance + $amount, 2),
                    'status' => 'open',
                ]);
            }

            if ($line->line_type === 'loan_deduction' && $line->source_type === EmployeeLoan::class && $line->source_id) {
                $loan = EmployeeLoan::query()->lockForUpdate()->find($line->source_id);
                if (! $loan) {
                    continue;
                }
                $amount = abs((float) $line->amount);
                $loan->update([
                    'amount_repaid' => max(0, round((float) $loan->amount_repaid - $amount, 2)),
                    'outstanding_principal' => round((float) $loan->outstanding_principal + $amount, 2),
                    'status' => 'active',
                ]);
            }
        }

        $record->update(['deductions_posted' => false]);
    }

    public function syncRunTotals(PayrollRun $run): PayrollRun
    {
        $records = $run->records()->get();
        $run->update([
            'total_gross' => round((float) $records->sum('gross_amount'), 2),
            'total_net' => round((float) $records->sum('net_payable'), 2),
            'total_paid' => round((float) $records->sum('amount_paid'), 2),
        ]);

        return $run->fresh();
    }
}