<?php

namespace App\Http\Requests\Dana;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DanaBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('dana-production.manage') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'production_date' => ['required', 'date'],
            'machine_id' => ['required', 'exists:machines,id'],
            'production_hall_id' => ['required', 'exists:production_halls,id'],
            'raw_material_warehouse_id' => ['required', 'exists:warehouses,id'],
            'finished_dana_warehouse_id' => ['required', 'exists:warehouses,id'],
            'waste_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'output_material_id' => ['required', 'exists:materials,id'],
            'output_material_variant_id' => ['nullable', 'exists:material_variants,id'],
            'dana_production_recipe_id' => ['nullable', 'exists:dana_production_recipes,id'],
            'output_kg' => ['required', 'numeric', 'min:0'],
            'reusable_waste_kg' => ['nullable', 'numeric', 'min:0'],
            'scrap_waste_kg' => ['nullable', 'numeric', 'min:0'],
            'process_loss_kg' => ['nullable', 'numeric', 'min:0'],
            'electricity_cost' => ['nullable', 'numeric', 'min:0'],
            'other_overhead_cost' => ['nullable', 'numeric', 'min:0'],
            'reusable_waste_material_id' => ['nullable', 'exists:materials,id'],
            'scrap_waste_material_id' => ['nullable', 'exists:materials,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'inputs' => ['nullable', 'array'],
            'inputs.*.material_id' => ['required_with:inputs', 'exists:materials,id'],
            'inputs.*.material_variant_id' => ['nullable', 'exists:material_variants,id'],
            'inputs.*.input_type' => ['nullable', Rule::in(array_keys(config('dana.input_types', [])))],
            'inputs.*.quantity_kg' => ['required_with:inputs', 'numeric', 'min:0.0001'],
            'operators' => ['nullable', 'array'],
            'operators.*.operator_name' => ['required_with:operators', 'string', 'max:120'],
            'operators.*.labor_cost' => ['nullable', 'numeric', 'min:0'],
            'operators.*.hours' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}