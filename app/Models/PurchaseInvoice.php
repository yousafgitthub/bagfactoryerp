<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseInvoice extends Model
{
    protected $fillable = [
        'invoice_number',
        'supplier_invoice_number',
        'supplier_id',
        'purchase_order_id',
        'invoice_date',
        'due_date',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'grand_total',
        'amount_paid',
        'notes',
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
            'amount_paid' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PurchaseInvoiceLine::class);
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