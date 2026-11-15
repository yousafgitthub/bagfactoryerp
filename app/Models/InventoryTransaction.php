<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InventoryTransaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'type',
        'status',
        'warehouse_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'transaction_date',
        'reference_type',
        'reference_id',
        'notes',
        'posted_at',
        'posted_by',
        'created_by',
        'reversal_of_id',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(InventoryTransactionItem::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function fromWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function stockAdjustment(): HasOne
    {
        return $this->hasOne(StockAdjustment::class);
    }

    public function stockTransfer(): HasOne
    {
        return $this->hasOne(StockTransfer::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(InventoryLedgerEntry::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }
}