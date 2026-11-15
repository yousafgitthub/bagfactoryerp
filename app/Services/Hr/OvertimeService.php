<?php

namespace App\Services\Hr;

use App\Models\Employee;
use App\Models\OvertimeRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OvertimeService
{
    public function __construct(
        private readonly EmployeePersistenceService $employeePersistence
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, ?User $actor = null): OvertimeRecord
    {
        $employee = Employee::query()->findOrFail($data['employee_id']);
        $rate = $this->resolveRate($employee, $data);
        $hours = (float) $data['hours'];
        $amount = round($hours * $rate, 2);

        return OvertimeRecord::query()->create([
            'employee_id' => $employee->id,
            'overtime_date' => $data['overtime_date'],
            'hours' => $hours,
            'rate_per_hour' => $rate,
            'calculated_amount' => $amount,
            'approval_status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'recorded_by' => $actor?->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveRate(Employee $employee, array $data): float
    {
        if (! empty($data['rate_per_hour'])) {
            return (float) $data['rate_per_hour'];
        }

        if ($employee->overtime_rate) {
            return (float) $employee->overtime_rate;
        }

        $historical = $this->employeePersistence->rateForDate($employee, $data['overtime_date']);
        if ($historical?->overtime_rate) {
            return (float) $historical->overtime_rate;
        }

        if ($employee->salary_type === 'monthly' && $employee->basic_monthly_salary) {
            $dayHours = (float) config('hr.standard_day_hours_for_overtime_rate', 8);
            $monthly = (float) $employee->basic_monthly_salary;
            $daily = $monthly / 30;

            return round($daily / $dayHours, 4);
        }

        if ($employee->salary_type === 'daily' && $employee->daily_wage_rate) {
            $dayHours = (float) config('hr.standard_day_hours_for_overtime_rate', 8);

            return round((float) $employee->daily_wage_rate / $dayHours, 4);
        }

        throw new HrException('No overtime rate configured for this employee.');
    }

    public function approve(OvertimeRecord $record, User $approver, string $decision, ?string $notes = null): OvertimeRecord
    {
        if ($record->approval_status !== 'pending') {
            throw new HrException('This overtime record has already been reviewed.');
        }

        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw new HrException('Invalid approval decision.');
        }

        return DB::transaction(function () use ($record, $approver, $decision, $notes) {
            $record->update([
                'approval_status' => $decision,
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'notes' => $notes ?? $record->notes,
            ]);

            return $record->fresh(['employee', 'approver']);
        });
    }
}