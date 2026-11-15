<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryBalance extends Model
{
    protected $fillable = [
        'warehouse_id',
        'material_id',
        'material_variant_id',
        'bucket',
        'quantity',
        'average_unit_cost',
        'total_value',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'average_unit_cost' => 'decimal:4',
            'total_value' => 'decimal:4',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
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