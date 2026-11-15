<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalaryRate extends Model
{
    protected $fillable = [
        'employee_id',
        'salary_type',
        'basic_monthly_salary',
        'daily_wage_rate',
        'overtime_rate',
        'effective_from',
        'effective_to',
        'created_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'basic_monthly_salary' => 'decimal:2',
            'daily_wage_rate' => 'decimal:2',
            'overtime_rate' => 'decimal:4',
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}