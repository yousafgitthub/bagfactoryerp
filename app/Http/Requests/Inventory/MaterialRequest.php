<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('inventory.manage') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $materialId = $this->route('material')?->id;

        return [
            'code' => ['required', 'string', 'max:32', Rule::unique('materials', 'code')->ignore($materialId)],
            'name' => ['required', 'string', 'max:255'],
            'material_category_id' => ['required', 'exists:material_categories,id'],
            'unit_of_measurement_id' => ['required', 'exists:units_of_measurement,id'],
            'default_unit_cost' => ['required', 'numeric', 'min:0'],
            'reorder_level' => ['required', 'numeric', 'min:0'],
            'inventory_bucket' => ['required', Rule::in(array_keys(config('inventory.inventory_buckets')))],
            'supports_variants' => ['sometimes', 'boolean'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'supports_variants' => $this->boolean('supports_variants'),
        ]);
    }
}