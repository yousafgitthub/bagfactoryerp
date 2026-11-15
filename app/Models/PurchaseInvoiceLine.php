<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseInvoiceLine extends Model
{
    protected $fillable = [
        'purchase_invoice_id',
        'purchase_order_line_id',
        'goods_receipt_line_id',
        'material_id',
        'material_variant_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'line_total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchaseInvoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}