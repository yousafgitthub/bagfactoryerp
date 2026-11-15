<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GlJournalEntry extends Model
{
    protected $fillable = [
        'entry_number',
        'entry_date',
        'status',
        'source_type',
        'source_id',
        'reversal_of_id',
        'description',
        'total_debit',
        'total_credit',
        'posted_at',
        'posted_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
            'total_debit' => 'float',
            'total_credit' => 'float',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(GlJournalLine::class)->orderBy('sort_order');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }
}