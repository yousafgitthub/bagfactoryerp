<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteEnquiry extends Model
{
    protected $fillable = [
        'type',
        'status',
        'idempotency_token',
        'name',
        'email',
        'phone',
        'phone_normalized',
        'company',
        'subject_key',
        'product_label',
        'message',
        'details',
        'artwork_path',
        'artwork_original_name',
        'customer_id',
        'internal_notes',
        'reviewed_at',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reference(): string
    {
        return 'ENQ-'.str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
}
