<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaBatchInventoryLink extends Model
{
    protected $fillable = [
        'dana_batch_id',
        'inventory_transaction_id',
        'link_type',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(DanaBatch::class, 'dana_batch_id');
    }

    public function inventoryTransaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class);
    }
}