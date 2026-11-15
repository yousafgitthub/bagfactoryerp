<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReceiptAllocation extends Model
{
    protected $fillable = [
        'customer_receipt_id',
        'sales_invoice_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
        ];
    }

    public function customerReceipt(): BelongsTo
    {
        return $this->belongsTo(CustomerReceipt::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }
}