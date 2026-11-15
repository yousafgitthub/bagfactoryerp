<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagBomLine extends Model
{
    protected $fillable = [
        'bag_bill_of_material_id',
        'material_id',
        'material_variant_id',
        'consumption_unit',
        'quantity_per_bag',
        'wastage_percent',
        'line_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity_per_bag' => 'decimal:6',
            'wastage_percent' => 'decimal:4',
        ];
    }

    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BagBillOfMaterial::class, 'bag_bill_of_material_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(MaterialVariant::class, 'material_variant_id');
    }

    public function quantityForBags(float $bagCount, ?float $weightPerBagG = null): float
    {
        $qty = (float) $this->quantity_per_bag * $bagCount;
        $wastage = 1 + ((float) $this->wastage_percent / 100);

        return round($qty * $wastage, 4);
    }
}