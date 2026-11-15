<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLoan extends Model
{
    protected $fillable = [
        'employee_id',
        'loan_date',
        'principal',
        'installment_amount',
        'installment_count',
        'amount_repaid',
        'outstanding_principal',
        'status',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'loan_date' => 'date',
            'principal' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'amount_repaid' => 'decimal:2',
            'outstanding_principal' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}