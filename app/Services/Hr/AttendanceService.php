<?php

namespace App\Services\Hr;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService
{
    public function __construct(
        private readonly AttendanceHoursCalculator $hoursCalculator
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data, ?User $actor = null, ?Attendance $existing = null): Attendance
    {
        if (! config('hr.shift_based_attendance', false)) {
            $duplicate = Attendance::query()
                ->where('employee_id', $data['employee_id'])
                ->whereDate('attendance_date', $data['attendance_date'])
                ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                ->exists();

            if ($duplicate) {
                throw new HrException('Attendance already exists for this employee on the selected date.');
            }
        }

        $data['working_hours'] = $this->hoursCalculator->resolve(
            $data['status'],
            $data['check_in_at'] ?? null,
            $data['check_out_at'] ?? null,
            $data['working_hours'] ?? null
        );

        $data['recorded_by'] = $actor?->id;

        return DB::transaction(function () use ($data, $existing) {
            if ($existing) {
                $existing->update($data);

                return $existing->fresh(['employee']);
            }

            return Attendance::query()->create($data)->load('employee');
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array{saved: int, errors: list<string>}
     */
    public function recordDailyBulk(Carbon|string $date, array $rows, ?User $actor = null): array
    {
        $saved = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            if (empty($row['employee_id']) || empty($row['status'])) {
                continue;
            }

            try {
                $this->record([
                    'employee_id' => $row['employee_id'],
                    'attendance_date' => Carbon::parse($date)->toDateString(),
                    'status' => $row['status'],
                    'check_in_at' => $row['check_in_at'] ?? null,
                    'check_out_at' => $row['check_out_at'] ?? null,
                    'working_hours' => $row['working_hours'] ?? null,
                    'notes' => $row['notes'] ?? null,
                ], $actor);
                $saved++;
            } catch (HrException $e) {
                $employee = Employee::query()->find($row['employee_id']);
                $errors[] = 'Row '.($index + 1).' ('.($employee?->employee_code ?? $row['employee_id']).'): '.$e->getMessage();
            }
        }

        return ['saved' => $saved, 'errors' => $errors];
    }
}