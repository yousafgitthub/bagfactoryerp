<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaBatchMaterialInput extends Model
{
    protected $fillable = [
        'dana_batch_id',
        'material_id',
        'material_variant_id',
        'input_type',
        'quantity_kg',
        'unit_cost_snapshot',
        'line_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:4',
            'unit_cost_snapshot' => 'decimal:4',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DanaBatch::class, 'dana_batch_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MaterialVariant::class, 'material_variant_id');
    }
}