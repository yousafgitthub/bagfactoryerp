<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'employee_code',
        'name',
        'phone',
        'address',
        'joining_date',
        'department_id',
        'designation_id',
        'salary_type',
        'basic_monthly_salary',
        'daily_wage_rate',
        'overtime_rate',
        'overtime_rule',
        'status',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'basic_monthly_salary' => 'decimal:2',
            'daily_wage_rate' => 'decimal:2',
            'overtime_rate' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    public function salaryRates(): HasMany
    {
        return $this->hasMany(EmployeeSalaryRate::class)->orderByDesc('effective_from');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function overtimeRecords(): HasMany
    {
        return $this->hasMany(OvertimeRecord::class);
    }

    public function productionAssignments(): HasMany
    {
        return $this->hasMany(EmployeeProductionAssignment::class);
    }
}