<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagProductVariant extends Model
{
    protected $fillable = [
        'bag_product_id',
        'variant_code',
        'name',
        'color_id',
        'weight_per_bag_g',
        'saleable_material_id',
        'saleable_material_variant_id',
        'rejected_material_id',
        'rejected_material_variant_id',
        'selling_price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'weight_per_bag_g' => 'decimal:4',
            'selling_price' => 'decimal:4',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(BagProduct::class, 'bag_product_id');
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function saleableMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'saleable_material_id');
    }

    public function saleableMaterialVariant(): BelongsTo
    {
        return $this->belongsTo(MaterialVariant::class, 'saleable_material_variant_id');
    }

    public function rejectedMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'rejected_material_id');
    }

    public function rejectedMaterialVariant(): BelongsTo
    {
        return $this->belongsTo(MaterialVariant::class, 'rejected_material_variant_id');
    }

    public function effectiveWeightPerBagG(): float
    {
        if ((float) $this->weight_per_bag_g > 0) {
            return (float) $this->weight_per_bag_g;
        }

        $product = $this->relationLoaded('product') ? $this->product : $this->product()->first();

        return (float) ($product?->nominal_weight_per_bag_g ?? 0);
    }
}