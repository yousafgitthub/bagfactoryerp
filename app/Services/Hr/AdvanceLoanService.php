<?php

namespace App\Services\Hr;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeLoan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdvanceLoanService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function recordAdvance(Employee $employee, array $data, ?User $actor = null): EmployeeAdvance
    {
        $amount = round((float) $data['amount'], 2);
        if ($amount <= 0) {
            throw new PayrollException('Advance amount must be greater than zero.');
        }

        return DB::transaction(function () use ($employee, $data, $amount, $actor) {
            return EmployeeAdvance::query()->create([
                'employee_id' => $employee->id,
                'advance_date' => $data['advance_date'],
                'amount' => $amount,
                'reason' => $data['reason'] ?? null,
                'amount_repaid' => 0,
                'outstanding_balance' => $amount,
                'per_payroll_deduction' => isset($data['per_payroll_deduction'])
                    ? round((float) $data['per_payroll_deduction'], 2)
                    : config('payroll.default_advance_payroll_deduction'),
                'status' => 'open',
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor?->id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordLoan(Employee $employee, array $data, ?User $actor = null): EmployeeLoan
    {
        $principal = round((float) $data['principal'], 2);
        $installment = round((float) $data['installment_amount'], 2);
        if ($principal <= 0 || $installment <= 0) {
            throw new PayrollException('Loan principal and installment must be greater than zero.');
        }

        return DB::transaction(function () use ($employee, $data, $principal, $installment, $actor) {
            return EmployeeLoan::query()->create([
                'employee_id' => $employee->id,
                'loan_date' => $data['loan_date'],
                'principal' => $principal,
                'installment_amount' => $installment,
                'installment_count' => $data['installment_count'] ?? null,
                'amount_repaid' => 0,
                'outstanding_principal' => $principal,
                'status' => 'active',
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $actor?->id,
            ]);
        });
    }
}