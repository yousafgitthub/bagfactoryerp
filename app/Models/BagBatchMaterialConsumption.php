<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagBatchMaterialConsumption extends Model
{
    protected $fillable = [
        'bag_production_batch_id',
        'material_id',
        'material_variant_id',
        'consumption_type',
        'quantity',
        'unit',
        'unit_cost_snapshot',
        'line_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost_snapshot' => 'decimal:4',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BagProductionBatch::class, 'bag_production_batch_id');
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