<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GlJournalLine extends Model
{
    protected $fillable = [
        'gl_journal_entry_id',
        'gl_account_id',
        'debit',
        'credit',
        'memo',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'debit' => 'float',
            'credit' => 'float',
        ];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(GlJournalEntry::class, 'gl_journal_entry_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'gl_account_id');
    }
}