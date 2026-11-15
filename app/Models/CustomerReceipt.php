<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerReceipt extends Model
{
    protected $fillable = [
        'receipt_number',
        'customer_id',
        'receipt_date',
        'amount',
        'payment_method',
        'reference',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(CustomerReceiptAllocation::class);
    }
}