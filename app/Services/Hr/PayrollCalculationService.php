<?php

namespace App\Services\Hr;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\OvertimeRecord;
use App\Models\PayrollRecord;
use App\Models\PayrollRecordLine;
use App\Models\PayrollRun;

class PayrollCalculationService
{
    public function __construct(
        private readonly EmployeePersistenceService $employeePersistence
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildCalculation(Employee $employee, PayrollRun $run): array
    {
        $periodEnd = $run->period_end->copy();
        $rate = $this->employeePersistence->rateForDate($employee, $periodEnd->toDateString());

        if (! $rate) {
            throw new PayrollException("No effective salary rate for {$employee->employee_code} in this period.");
        }

        $salaryType = $rate->salary_type;
        $lines = [];
        $attendanceSummary = $this->summarizeAttendance($employee, $run);

        if ($salaryType === 'monthly') {
            $basic = (float) ($rate->basic_monthly_salary ?? 0);
            $divisor = (float) config('payroll.monthly_absence_divisor', 30);
            $perDay = $divisor > 0 ? $basic / $divisor : 0;
            $absenceDeduction = round($perDay * $attendanceSummary['absent_days'], 2);

            $lines[] = $this->line('basic', 'Monthly basic salary', $basic);
            if ($absenceDeduction > 0) {
                $lines[] = $this->line(
                    'absence_deduction',
                    "Absence deduction ({$attendanceSummary['absent_days']} days)",
                    -$absenceDeduction,
                );
            }

            $payableDays = $attendanceSummary['payable_days'];
            $absentDays = $attendanceSummary['absent_days'];
        } else {
            $dailyRate = (float) ($rate->daily_wage_rate ?? 0);
            $payableAmount = round($dailyRate * $attendanceSummary['payable_days'], 2);
            $lines[] = $this->line(
                'basic',
                "Daily wage ({$attendanceSummary['payable_days']} payable days)",
                $payableAmount,
            );

            $payableDays = $attendanceSummary['payable_days'];
            $absentDays = $attendanceSummary['absent_days'];
        }

        $overtime = $this->sumApprovedOvertime($employee, $run);
        if ($overtime['hours'] > 0) {
            $lines[] = $this->line(
                'overtime',
                "Approved overtime ({$overtime['hours']} h)",
                $overtime['amount'],
                OvertimeRecord::class,
            );
        }

        $advanceLines = $this->plannedAdvanceDeductions($employee);
        foreach ($advanceLines as $advLine) {
            $lines[] = $advLine;
        }

        $loanLines = $this->plannedLoanDeductions($employee);
        foreach ($loanLines as $loanLine) {
            $lines[] = $loanLine;
        }

        $earnings = 0.0;
        $deductions = 0.0;
        foreach ($lines as $line) {
            $amount = (float) $line['amount'];
            if ($amount >= 0) {
                $earnings += $amount;
            } else {
                $deductions += abs($amount);
            }
        }

        $gross = round($earnings, 2);
        $deductionsTotal = round($deductions, 2);
        $net = max(0, round($gross - $deductionsTotal, 2));

        return [
            'salary_type' => $salaryType,
            'basic_monthly_salary' => $rate->basic_monthly_salary,
            'daily_wage_rate' => $rate->daily_wage_rate,
            'employee_salary_rate_id' => $rate->id,
            'payable_days' => $payableDays,
            'absent_days' => $absentDays,
            'overtime_hours' => $overtime['hours'],
            'overtime_amount' => $overtime['amount'],
            'bonuses_total' => 0,
            'allowances_total' => 0,
            'deductions_total' => $deductionsTotal,
            'gross_amount' => $gross,
            'net_payable' => $net,
            'lines' => $lines,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $manualLines
     */
    public function applyManualLines(PayrollRecord $record, array $manualLines): void
    {
        foreach ($manualLines as $entry) {
            if (empty($entry['line_type']) || ! isset($entry['amount'])) {
                continue;
            }
            $type = (string) $entry['line_type'];
            if (! in_array($type, ['bonus', 'allowance', 'other_deduction'], true)) {
                continue;
            }
            $amount = (float) $entry['amount'];
            if (in_array($type, ['other_deduction'], true)) {
                $amount = -abs($amount);
            } else {
                $amount = abs($amount);
            }

            PayrollRecordLine::query()->create([
                'payroll_record_id' => $record->id,
                'line_type' => $type,
                'description' => $entry['description'] ?? config("payroll.line_types.{$type}"),
                'amount' => $amount,
            ]);
        }

        $this->recomputeTotals($record);
    }

    public function recomputeTotals(PayrollRecord $record): PayrollRecord
    {
        $record->load('lines');

        $earnings = 0.0;
        $deductions = 0.0;
        $bonuses = 0.0;
        $allowances = 0.0;
        $overtimeHours = 0.0;
        $overtimeAmount = 0.0;

        foreach ($record->lines as $line) {
            $amount = (float) $line->amount;
            if ($line->line_type === 'bonus') {
                $bonuses += $amount;
            }
            if ($line->line_type === 'allowance') {
                $allowances += $amount;
            }
            if ($line->line_type === 'overtime') {
                $overtimeAmount += $amount;
            }

            if ($amount >= 0) {
                $earnings += $amount;
            } else {
                $deductions += abs($amount);
            }
        }

        $gross = round($earnings, 2);
        $deductionsTotal = round($deductions, 2);
        $net = max(0, round($gross - $deductionsTotal, 2));
        $paid = (float) $record->amount_paid;
        $outstanding = max(0, round($net - $paid, 2));

        $record->update([
            'bonuses_total' => round($bonuses, 2),
            'allowances_total' => round($allowances, 2),
            'overtime_amount' => round($overtimeAmount, 2),
            'deductions_total' => $deductionsTotal,
            'gross_amount' => $gross,
            'net_payable' => $net,
            'outstanding_amount' => $outstanding,
        ]);

        return $record->fresh();
    }

    /**
     * @return array{payable_days: float, absent_days: float}
     */
    private function summarizeAttendance(Employee $employee, PayrollRun $run): array
    {
        $weights = config('payroll.attendance_pay_weights', []);
        $halfFraction = (float) config('payroll.monthly_half_day_deduction_fraction', 0.5);
        $salaryType = $employee->salary_type;

        $rows = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$run->period_start, $run->period_end])
            ->get();

        $payable = 0.0;
        $absent = 0.0;

        foreach ($rows as $row) {
            $status = $row->status;
            $weight = (float) ($weights[$status] ?? 0);

            if ($salaryType === 'daily') {
                $payable += $weight;
                if ($weight <= 0) {
                    $absent += 1;
                }
            } else {
                if ($status === 'half_day') {
                    $payable += 1 - $halfFraction;
                    $absent += $halfFraction;
                } elseif ($weight >= 1) {
                    $payable += 1;
                } else {
                    $absent += 1;
                }
            }
        }

        return [
            'payable_days' => round($payable, 2),
            'absent_days' => round($absent, 2),
        ];
    }

    /**
     * @return array{hours: float, amount: float}
     */
    private function sumApprovedOvertime(Employee $employee, PayrollRun $run): array
    {
        $rows = OvertimeRecord::query()
            ->where('employee_id', $employee->id)
            ->where('approval_status', 'approved')
            ->whereBetween('overtime_date', [$run->period_start, $run->period_end])
            ->get();

        $hours = (float) $rows->sum('hours');
        $amount = (float) $rows->sum('calculated_amount');

        return ['hours' => round($hours, 2), 'amount' => round($amount, 2)];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function plannedAdvanceDeductions(Employee $employee): array
    {
        $lines = [];

        EmployeeAdvance::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'open')
            ->where('outstanding_balance', '>', 0)
            ->orderBy('advance_date')
            ->get()
            ->each(function (EmployeeAdvance $advance) use (&$lines) {
                $cap = $advance->per_payroll_deduction !== null
                    ? (float) $advance->per_payroll_deduction
                    : (float) $advance->outstanding_balance;
                $deduct = min((float) $advance->outstanding_balance, $cap);
                if ($deduct <= 0) {
                    return;
                }
                $lines[] = $this->line(
                    'advance_deduction',
                    "Advance recovery #{$advance->id}",
                    -$deduct,
                    EmployeeAdvance::class,
                    $advance->id,
                );
            });

        return $lines;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function plannedLoanDeductions(Employee $employee): array
    {
        $lines = [];

        EmployeeLoan::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'active')
            ->where('outstanding_principal', '>', 0)
            ->orderBy('loan_date')
            ->get()
            ->each(function (EmployeeLoan $loan) use (&$lines) {
                $installment = min((float) $loan->installment_amount, (float) $loan->outstanding_principal);
                if ($installment <= 0) {
                    return;
                }
                $lines[] = $this->line(
                    'loan_deduction',
                    "Loan installment #{$loan->id}",
                    -$installment,
                    EmployeeLoan::class,
                    $loan->id,
                );
            });

        return $lines;
    }

    /**
     * @return array<string, mixed>
     */
    private function line(string $type, string $description, float $amount, ?string $sourceType = null, ?int $sourceId = null): array
    {
        return [
            'line_type' => $type,
            'description' => $description,
            'amount' => round($amount, 2),
            'source_type' => $sourceType,
            'source_id' => $sourceId,
        ];
    }
}