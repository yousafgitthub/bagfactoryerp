<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagBatchWaste extends Model
{
    protected $fillable = [
        'bag_production_batch_id',
        'waste_type',
        'material_id',
        'warehouse_id',
        'quantity_kg',
        'recorded_at',
        'inventory_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'decimal:4',
            'recorded_at' => 'datetime',
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

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}