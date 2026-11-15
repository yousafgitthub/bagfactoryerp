<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagBatchInventoryLink extends Model
{
    protected $fillable = [
        'bag_production_batch_id',
        'inventory_transaction_id',
        'link_type',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(BagProductionBatch::class, 'bag_production_batch_id');
    }

    public function inventoryTransaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class);
    }
}