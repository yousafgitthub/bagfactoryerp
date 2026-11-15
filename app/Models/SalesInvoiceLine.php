<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesInvoiceLine extends Model
{
    protected $fillable = [
        'sales_invoice_id',
        'sales_order_line_id',
        'bag_product_id',
        'bag_product_variant_id',
        'material_id',
        'material_variant_id',
        'unit_of_measurement_id',
        'size_label',
        'color_label',
        'quantity',
        'unit_price',
        'unit_cost',
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
            'unit_cost' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function bagProduct(): BelongsTo
    {
        return $this->belongsTo(BagProduct::class);
    }

    public function bagProductVariant(): BelongsTo
    {
        return $this->belongsTo(BagProductVariant::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}