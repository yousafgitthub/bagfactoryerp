<?php

namespace App\Http\Requests\Bag;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BagBatchRequest extends FormRequest
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
            'bag_production_order_id' => ['required', 'exists:bag_production_orders,id'],
            'production_date' => ['required', 'date'],
            'machine_id' => ['required', 'exists:machines,id'],
            'production_hall_id' => ['required', 'exists:production_halls,id'],
            'bag_bill_of_material_id' => ['nullable', 'exists:bag_bill_of_materials,id'],
            'good_quantity' => ['required', 'numeric', 'min:0'],
            'rejected_quantity' => ['nullable', 'numeric', 'min:0'],
            'dana_consumed_kg' => ['required', 'numeric', 'min:0.0001'],
            'color_material_consumed_kg' => ['nullable', 'numeric', 'min:0'],
            'actual_weight_per_good_bag_g' => ['nullable', 'numeric', 'min:0.0001'],
            'reusable_waste_kg' => ['nullable', 'numeric', 'min:0'],
            'scrap_waste_kg' => ['nullable', 'numeric', 'min:0'],
            'process_loss_kg' => ['nullable', 'numeric', 'min:0'],
            'electricity_cost' => ['nullable', 'numeric', 'min:0'],
            'other_overhead_cost' => ['nullable', 'numeric', 'min:0'],
            'finished_goods_warehouse_id' => ['required', 'exists:warehouses,id'],
            'dana_warehouse_id' => ['required', 'exists:warehouses,id'],
            'materials_warehouse_id' => ['required', 'exists:warehouses,id'],
            'rejected_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'waste_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'reusable_waste_material_id' => ['nullable', 'exists:materials,id'],
            'scrap_waste_material_id' => ['nullable', 'exists:materials,id'],
            'production_time_hours' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'consumptions' => ['nullable', 'array'],
            'consumptions.*.material_id' => ['required_with:consumptions', 'exists:materials,id'],
            'consumptions.*.material_variant_id' => ['nullable', 'exists:material_variants,id'],
            'consumptions.*.consumption_type' => ['nullable', Rule::in(array_keys(config('bag.consumption_types', [])))],
            'consumptions.*.quantity' => ['required_with:consumptions', 'numeric', 'min:0'],
            'operators' => ['nullable', 'array'],
            'operators.*.operator_name' => ['required_with:operators', 'string', 'max:120'],
            'operators.*.labor_cost' => ['nullable', 'numeric', 'min:0'],
            'operators.*.hours' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}