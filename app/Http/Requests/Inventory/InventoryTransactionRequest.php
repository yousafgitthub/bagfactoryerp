<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryTransactionRequest extends FormRequest
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
        $type = $this->string('type')->toString();

        $rules = [
            'type' => ['required', Rule::in(array_keys(config('inventory.transaction_types')))],
            'transaction_date' => ['required', 'date'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'from_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'to_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.material_id' => ['required', 'exists:materials,id'],
            'items.*.material_variant_id' => ['nullable', 'exists:material_variants,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'adjustment_reason' => ['nullable', 'string', 'max:255'],
            'adjustment_direction' => ['nullable', Rule::in(['increase', 'decrease'])],
        ];

        if ($type === 'warehouse_transfer') {
            $rules['from_warehouse_id'] = ['required', 'exists:warehouses,id', 'different:to_warehouse_id'];
            $rules['to_warehouse_id'] = ['required', 'exists:warehouses,id'];
        } elseif ($type !== 'warehouse_transfer') {
            $rules['warehouse_id'] = ['required', 'exists:warehouses,id'];
        }

        if ($type === 'adjustment') {
            $rules['adjustment_reason'] = ['required', 'string', 'max:255'];
            $rules['adjustment_direction'] = ['required', Rule::in(['increase', 'decrease'])];
        }

        return $rules;
    }
}