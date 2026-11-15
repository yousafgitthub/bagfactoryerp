<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeAdvance extends Model
{
    protected $fillable = [
        'employee_id',
        'advance_date',
        'amount',
        'reason',
        'amount_repaid',
        'outstanding_balance',
        'per_payroll_deduction',
        'status',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'advance_date' => 'date',
            'amount' => 'decimal:2',
            'amount_repaid' => 'decimal:2',
            'outstanding_balance' => 'decimal:2',
            'per_payroll_deduction' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}