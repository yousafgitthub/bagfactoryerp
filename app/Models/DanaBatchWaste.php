<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaBatchWaste extends Model
{
    protected $fillable = [
        'dana_batch_id',
        'waste_type',
        'material_id',
        'warehouse_id',
        'quantity',
        'recorded_at',
        'inventory_transaction_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:4',
            'recorded_at' => 'datetime',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DanaBatch::class, 'dana_batch_id');
    }
}