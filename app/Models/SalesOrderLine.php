<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesOrderLine extends Model
{
    protected $fillable = [
        'sales_order_id',
        'bag_product_id',
        'bag_product_variant_id',
        'material_id',
        'material_variant_id',
        'unit_of_measurement_id',
        'size_label',
        'color_label',
        'ordered_qty',
        'invoiced_qty',
        'returned_qty',
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
            'ordered_qty' => 'decimal:4',
            'invoiced_qty' => 'decimal:4',
            'returned_qty' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
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

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'unit_of_measurement_id');
    }

    public function remainingInvoiceableQty(): float
    {
        return max(0, (float) $this->ordered_qty - (float) $this->invoiced_qty);
    }
}