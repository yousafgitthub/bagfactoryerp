<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BagBillOfMaterial extends Model
{
    protected $table = 'bag_bill_of_materials';

    protected $fillable = [
        'code',
        'name',
        'bag_product_id',
        'bag_product_variant_id',
        'version',
        'effective_from',
        'effective_to',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(BagProduct::class, 'bag_product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(BagProductVariant::class, 'bag_product_variant_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BagBomLine::class)->orderBy('line_order');
    }

    public function isActiveOn(\DateTimeInterface|string $date): bool
    {
        $day = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : $date;

        if ($this->status !== 'active') {
            return false;
        }

        if ($this->effective_from->format('Y-m-d') > $day) {
            return false;
        }

        if ($this->effective_to && $this->effective_to->format('Y-m-d') < $day) {
            return false;
        }

        return true;
    }
}