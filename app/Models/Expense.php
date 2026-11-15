<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    protected $fillable = [
        'expense_number',
        'expense_category_id',
        'expense_date',
        'amount',
        'payment_status',
        'payment_method',
        'treasury_account_id',
        'description',
        'attachment_path',
        'gl_journal_entry_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'float',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function treasuryAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(GlJournalEntry::class, 'gl_journal_entry_id');
    }
}