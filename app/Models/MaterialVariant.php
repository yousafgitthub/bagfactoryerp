<?php

namespace App\Models;

use App\Models\Concerns\HasMasterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaterialVariant extends Model
{
    use HasMasterStatus;

    protected $fillable = [
        'material_id',
        'color_id',
        'variant_code',
        'name',
        'status',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }
}