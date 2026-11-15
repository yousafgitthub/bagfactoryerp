<?php

namespace App\Http\Requests\Hr;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductionAssignmentRequest extends FormRequest
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
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'assignment_type' => ['required', Rule::in(array_keys(config('hr.production_assignment_types', [])))],
            'department_id' => ['nullable', 'exists:departments,id'],
            'machine_id' => ['nullable', 'exists:machines,id'],
            'dana_batch_id' => ['nullable', 'exists:dana_batches,id'],
            'bag_production_batch_id' => ['nullable', 'exists:bag_production_batches,id'],
            'bag_production_order_id' => ['nullable', 'exists:bag_production_orders,id'],
            'assigned_from' => ['nullable', 'date'],
            'assigned_to' => ['nullable', 'date', 'after_or_equal:assigned_from'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}