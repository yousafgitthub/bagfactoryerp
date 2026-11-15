<?php

namespace App\Models;

use App\Models\Concerns\HasMasterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreasuryAccount extends Model
{
    use HasMasterStatus;

    protected $fillable = [
        'code',
        'name',
        'account_type',
        'gl_account_id',
        'opening_balance',
        'current_balance',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'float',
            'current_balance' => 'float',
        ];
    }

    public function glAccount(): BelongsTo
    {
        return $this->belongsTo(GlAccount::class, 'gl_account_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(TreasuryMovement::class);
    }
}