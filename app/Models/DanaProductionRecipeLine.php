<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaProductionRecipeLine extends Model
{
    protected $fillable = [
        'dana_production_recipe_id',
        'material_id',
        'material_variant_id',
        'input_type',
        'quantity_per_output_kg',
        'line_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity_per_output_kg' => 'decimal:6',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(DanaProductionRecipe::class, 'dana_production_recipe_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}