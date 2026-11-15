<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagBatchOperator extends Model
{
    protected $fillable = [
        'bag_production_batch_id',
        'operator_name',
        'labor_cost',
        'hours',
        'line_order',
    ];

    protected function casts(): array
    {
        return [
            'labor_cost' => 'decimal:4',
            'hours' => 'decimal:2',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BagProductionBatch::class, 'bag_production_batch_id');
    }
}