<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreasuryMovement extends Model
{
    protected $fillable = [
        'movement_number',
        'treasury_account_id',
        'counter_treasury_account_id',
        'movement_type',
        'movement_date',
        'amount',
        'reference',
        'notes',
        'gl_journal_entry_id',
        'source_type',
        'source_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'movement_date' => 'date',
            'amount' => 'float',
        ];
    }

    public function treasuryAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class);
    }

    public function counterTreasuryAccount(): BelongsTo
    {
        return $this->belongsTo(TreasuryAccount::class, 'counter_treasury_account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(GlJournalEntry::class, 'gl_journal_entry_id');
    }
}