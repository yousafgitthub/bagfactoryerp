<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderLine extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'material_id',
        'material_variant_id',
        'unit_of_measurement_id',
        'ordered_qty',
        'received_qty',
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
            'received_qty' => 'decimal:4',
            'invoiced_qty' => 'decimal:4',
            'returned_qty' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'discount_amount' => 'decimal:2',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MaterialVariant::class, 'material_variant_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'unit_of_measurement_id');
    }

    public function remainingReceivableQty(): float
    {
        return max(0, (float) $this->ordered_qty - (float) $this->received_qty);
    }

    public function remainingInvoiceableQty(): float
    {
        return max(0, (float) $this->received_qty - (float) $this->invoiced_qty);
    }
}