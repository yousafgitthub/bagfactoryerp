<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesInvoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'customer_id',
        'sales_order_id',
        'warehouse_id',
        'invoice_date',
        'due_date',
        'sale_type',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'cogs_total',
        'amount_paid',
        'notes',
        'inventory_transaction_id',
        'posted_at',
        'posted_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'cogs_total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SalesInvoiceLine::class);
    }

    public function inventoryTransaction(): BelongsTo
    {
        return $this->belongsTo(InventoryTransaction::class);
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function outstandingAmount(): float
    {
        return max(0, (float) $this->grand_total - (float) $this->amount_paid);
    }
}