<?php

namespace App\Http\Requests\Bag;

use Illuminate\Foundation\Http\FormRequest;

class BagOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('shopping-bag-production.manage') === true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bag_product_id' => ['required', 'exists:bag_products,id'],
            'bag_product_variant_id' => ['required', 'exists:bag_product_variants,id'],
            'bag_bill_of_material_id' => ['nullable', 'exists:bag_bill_of_materials,id'],
            'planned_quantity' => ['required', 'numeric', 'min:1'],
            'machine_id' => ['nullable', 'exists:machines,id'],
            'production_hall_id' => ['nullable', 'exists:production_halls,id'],
            'color_id' => ['nullable', 'exists:colors,id'],
            'planned_start_date' => ['nullable', 'date'],
            'planned_end_date' => ['nullable', 'date', 'after_or_equal:planned_start_date'],
            'finished_goods_warehouse_id' => ['required', 'exists:warehouses,id'],
            'dana_warehouse_id' => ['required', 'exists:warehouses,id'],
            'materials_warehouse_id' => ['required', 'exists:warehouses,id'],
            'rejected_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'waste_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'assignments' => ['nullable', 'array'],
            'assignments.*.employee_name' => ['required_with:assignments', 'string', 'max:120'],
            'assignments.*.role' => ['nullable', 'string', 'max:64'],
        ];
    }
}