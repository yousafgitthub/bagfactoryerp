<?php

namespace App\Http\Requests\Hr;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('employees.manage') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employeeId = $this->route('employee')?->id;

        return [
            'employee_code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('employees', 'employee_code')->ignore($employeeId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:2000'],
            'joining_date' => ['required', 'date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'salary_type' => ['required', Rule::in(array_keys(config('hr.salary_types', [])))],
            'basic_monthly_salary' => ['nullable', 'numeric', 'min:0'],
            'daily_wage_rate' => ['nullable', 'numeric', 'min:0'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0'],
            'overtime_rule' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(config('hr.employee_statuses', [])))],
            'notes' => ['nullable', 'string', 'max:2000'],
            'salary_effective_from' => ['nullable', 'date'],
            'salary_rate_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}