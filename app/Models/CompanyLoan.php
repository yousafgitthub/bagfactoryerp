<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyLoan extends Model
{
    protected $fillable = [
        'loan_number',
        'direction',
        'counterparty_name',
        'principal',
        'outstanding_principal',
        'start_date',
        'maturity_date',
        'status',
        'interest_rate',
        'notes',
        'gl_journal_entry_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'maturity_date' => 'date',
            'principal' => 'float',
            'outstanding_principal' => 'float',
            'interest_rate' => 'float',
        ];
    }

    public function repayments(): HasMany
    {
        return $this->hasMany(CompanyLoanRepayment::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(GlJournalEntry::class, 'gl_journal_entry_id');
    }
}