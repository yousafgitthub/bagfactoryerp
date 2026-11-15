<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRecord extends Model
{
    protected $fillable = [
        'employee_id',
        'overtime_date',
        'hours',
        'rate_per_hour',
        'calculated_amount',
        'approval_status',
        'approved_by',
        'approved_at',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'overtime_date' => 'date',
            'hours' => 'decimal:2',
            'rate_per_hour' => 'decimal:4',
            'calculated_amount' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}