<?php

namespace App\Http\Requests\Hr;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DailyAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('attendance.manage') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attendance_date' => ['required', 'date'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.employee_id' => ['required', 'exists:employees,id'],
            'rows.*.status' => ['required', Rule::in(array_keys(config('hr.attendance_statuses', [])))],
            'rows.*.check_in_at' => ['nullable', 'date_format:H:i'],
            'rows.*.check_out_at' => ['nullable', 'date_format:H:i'],
            'rows.*.working_hours' => ['nullable', 'numeric', 'min:0', 'max:24'],
            'rows.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}