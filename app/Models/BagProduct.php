<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BagProduct extends Model
{
    protected $fillable = [
        'code',
        'name',
        'bag_type',
        'size_label',
        'width_mm',
        'length_mm',
        'thickness_micron',
        'nominal_weight_per_bag_g',
        'color_id',
        'unit_of_measurement_id',
        'selling_price',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'width_mm' => 'decimal:2',
            'length_mm' => 'decimal:2',
            'thickness_micron' => 'decimal:2',
            'nominal_weight_per_bag_g' => 'decimal:4',
            'selling_price' => 'decimal:4',
        ];
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'unit_of_measurement_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(BagProductVariant::class);
    }

    public function billsOfMaterial(): HasMany
    {
        return $this->hasMany(BagBillOfMaterial::class);
    }
}