<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReturnLine extends Model
{
    protected $fillable = [
        'sales_return_id',
        'sales_invoice_line_id',
        'bag_product_variant_id',
        'material_id',
        'material_variant_id',
        'quantity',
        'condition',
        'disposition',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'line_total',
        'inspection_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'unit_price' => 'decimal:4',
            'tax_rate' => 'decimal:4',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function salesReturn(): BelongsTo
    {
        return $this->belongsTo(SalesReturn::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function bagProductVariant(): BelongsTo
    {
        return $this->belongsTo(BagProductVariant::class);
    }
}