<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockLot extends Model
{
    protected $fillable = [
        'material_id',
        'material_variant_id',
        'warehouse_id',
        'lot_number',
        'received_at',
        'quantity_received',
        'quantity_remaining',
        'unit_cost',
        'source_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'quantity_received' => 'decimal:4',
            'quantity_remaining' => 'decimal:4',
            'unit_cost' => 'decimal:4',
        ];
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}