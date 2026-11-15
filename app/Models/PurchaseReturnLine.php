<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturnLine extends Model
{
    protected $fillable = [
        'purchase_return_id',
        'purchase_order_line_id',
        'material_id',
        'material_variant_id',
        'quantity',
        'unit_cost',
        'unit_price',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchaseReturn(): BelongsTo
    {
        return $this->belongsTo(PurchaseReturn::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}