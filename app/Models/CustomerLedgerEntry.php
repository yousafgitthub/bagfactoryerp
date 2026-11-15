<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerLedgerEntry extends Model
{
    protected $fillable = [
        'customer_id',
        'entry_date',
        'entry_type',
        'reference_type',
        'reference_id',
        'document_number',
        'description',
        'debit',
        'credit',
        'running_balance',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'debit' => 'decimal:2',
            'credit' => 'decimal:2',
            'running_balance' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}