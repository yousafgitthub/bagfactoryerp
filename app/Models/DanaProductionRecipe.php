<?php

namespace App\Models;

use App\Models\Concerns\HasMasterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DanaProductionRecipe extends Model
{
    use HasMasterStatus;

    protected $fillable = [
        'code',
        'name',
        'output_material_id',
        'output_material_variant_id',
        'max_process_loss_percent',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'max_process_loss_percent' => 'decimal:4',
        ];
    }

    public function outputMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'output_material_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(DanaProductionRecipeLine::class)->orderBy('line_order');
    }
}