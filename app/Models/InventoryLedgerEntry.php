<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryLedgerEntry extends Model
{
    protected $fillable = [
        'inventory_transaction_id',
        'inventory_transaction_item_id',
        'movement_type',
        'warehouse_id',
        'material_id',
        'material_variant_id',
        'quantity',
        'unit_cost',
        'total_value',
        'occurred_at',
        'reference',
        'stock_lot_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'total_value' => 'decimal:4',
            'occurred_at' => 'datetime',
        ];
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class, 'inventory_transaction_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}