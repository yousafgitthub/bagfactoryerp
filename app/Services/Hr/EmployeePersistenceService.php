<?php

namespace App\Services\Hr;

use App\Models\Employee;
use App\Models\EmployeeSalaryRate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeePersistenceService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, ?User $actor = null): Employee
    {
        return DB::transaction(function () use ($data, $actor) {
            $data['is_active'] = ($data['status'] ?? 'active') === 'active';
            $employee = Employee::query()->create($data);

            $this->openSalaryRate($employee, $data, $actor, $employee->joining_date);

            return $employee->fresh(['department', 'designation', 'salaryRates']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data, ?User $actor = null): Employee
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $salaryChanged = $this->salaryFieldsChanged($employee, $data);
            $data['is_active'] = ($data['status'] ?? $employee->status) === 'active';

            $employee->update($data);

            if ($salaryChanged) {
                $effectiveFrom = Carbon::parse($data['salary_effective_from'] ?? now()->toDateString());
                $this->closeCurrentSalaryRate($employee, $effectiveFrom->copy()->subDay());
                $this->openSalaryRate($employee, $data, $actor, $effectiveFrom);
            }

            return $employee->fresh(['department', 'designation', 'salaryRates']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function salaryFieldsChanged(Employee $employee, array $data): bool
    {
        $keys = ['salary_type', 'basic_monthly_salary', 'daily_wage_rate', 'overtime_rate'];

        foreach ($keys as $key) {
            if (! array_key_exists($key, $data)) {
                continue;
            }
            $current = $employee->getAttribute($key);
            $incoming = $data[$key];
            if ((string) $current !== (string) $incoming) {
                return true;
            }
        }

        return false;
    }

    private function closeCurrentSalaryRate(Employee $employee, Carbon $effectiveTo): void
    {
        EmployeeSalaryRate::query()
            ->where('employee_id', $employee->id)
            ->whereNull('effective_to')
            ->update(['effective_to' => $effectiveTo->toDateString()]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function openSalaryRate(Employee $employee, array $data, ?User $actor, Carbon|string $effectiveFrom): void
    {
        EmployeeSalaryRate::query()->create([
            'employee_id' => $employee->id,
            'salary_type' => $data['salary_type'] ?? $employee->salary_type,
            'basic_monthly_salary' => $data['basic_monthly_salary'] ?? $employee->basic_monthly_salary,
            'daily_wage_rate' => $data['daily_wage_rate'] ?? $employee->daily_wage_rate,
            'overtime_rate' => $data['overtime_rate'] ?? $employee->overtime_rate,
            'effective_from' => Carbon::parse($effectiveFrom)->toDateString(),
            'effective_to' => null,
            'created_by' => $actor?->id,
            'notes' => $data['salary_rate_notes'] ?? null,
        ]);
    }

    public function rateForDate(Employee $employee, Carbon|string $date): ?EmployeeSalaryRate
    {
        $day = Carbon::parse($date)->toDateString();

        return EmployeeSalaryRate::query()
            ->where('employee_id', $employee->id)
            ->where('effective_from', '<=', $day)
            ->where(function ($q) use ($day) {
                $q->whereNull('effective_to')->orWhere('effective_to', '>=', $day);
            })
            ->orderByDesc('effective_from')
            ->first();
    }
}