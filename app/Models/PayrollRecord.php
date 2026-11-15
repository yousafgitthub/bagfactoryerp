<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollRecord extends Model
{
    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'period_start',
        'period_end',
        'salary_type',
        'basic_monthly_salary',
        'daily_wage_rate',
        'employee_salary_rate_id',
        'payable_days',
        'absent_days',
        'overtime_hours',
        'overtime_amount',
        'bonuses_total',
        'allowances_total',
        'deductions_total',
        'gross_amount',
        'net_payable',
        'amount_paid',
        'outstanding_amount',
        'status',
        'deductions_posted',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'basic_monthly_salary' => 'decimal:2',
            'daily_wage_rate' => 'decimal:2',
            'payable_days' => 'decimal:2',
            'absent_days' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'bonuses_total' => 'decimal:2',
            'allowances_total' => 'decimal:2',
            'deductions_total' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'net_payable' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'outstanding_amount' => 'decimal:2',
            'deductions_posted' => 'boolean',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class, 'payroll_run_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function salaryRate(): BelongsTo
    {
        return $this->belongsTo(EmployeeSalaryRate::class, 'employee_salary_rate_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayrollRecordLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PayrollPayment::class);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollAdjustment::class);
    }
}