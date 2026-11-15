<?php

namespace App\Http\Requests\Dana;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DanaRecipeRequest extends FormRequest
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
        $recipeId = $this->route('recipe')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('dana_production_recipes', 'code')->ignore($recipeId),
            ],
            'name' => ['required', 'string', 'max:120'],
            'output_material_id' => ['required', 'exists:materials,id'],
            'output_material_variant_id' => ['nullable', 'exists:material_variants,id'],
            'max_process_loss_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'lines' => ['nullable', 'array'],
            'lines.*.material_id' => ['required_with:lines', 'exists:materials,id'],
            'lines.*.material_variant_id' => ['nullable', 'exists:material_variants,id'],
            'lines.*.input_type' => ['nullable', Rule::in(array_keys(config('dana.input_types', [])))],
            'lines.*.quantity_per_output_kg' => ['required_with:lines', 'numeric', 'min:0.000001'],
        ];
    }
}