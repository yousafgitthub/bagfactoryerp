<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoodsReceiptLine extends Model
{
    protected $fillable = [
        'goods_receipt_id',
        'purchase_order_line_id',
        'material_id',
        'material_variant_id',
        'received_qty',
        'unit_price',
        'unit_cost',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'received_qty' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'unit_cost' => 'decimal:4',
            'line_total' => 'decimal:2',
        ];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderLine(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderLine::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}