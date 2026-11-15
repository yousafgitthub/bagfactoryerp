<?php

namespace App\Models;

use App\Models\Concerns\HasMasterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Material extends Model
{
    use HasMasterStatus;

    protected $fillable = [
        'code',
        'name',
        'material_category_id',
        'unit_of_measurement_id',
        'default_unit_cost',
        'reorder_level',
        'inventory_bucket',
        'supports_variants',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'default_unit_cost' => 'decimal:4',
            'reorder_level' => 'decimal:4',
            'supports_variants' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MaterialCategory::class, 'material_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'unit_of_measurement_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MaterialVariant::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(InventoryBalance::class);
    }
}