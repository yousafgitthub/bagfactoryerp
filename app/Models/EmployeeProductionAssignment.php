<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeProductionAssignment extends Model
{
    protected $fillable = [
        'employee_id',
        'assignment_type',
        'department_id',
        'machine_id',
        'dana_batch_id',
        'bag_production_batch_id',
        'bag_production_order_id',
        'assigned_from',
        'assigned_to',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'assigned_from' => 'date',
            'assigned_to' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function danaBatch(): BelongsTo
    {
        return $this->belongsTo(DanaBatch::class);
    }

    public function bagProductionBatch(): BelongsTo
    {
        return $this->belongsTo(BagProductionBatch::class);
    }

    public function bagProductionOrder(): BelongsTo
    {
        return $this->belongsTo(BagProductionOrder::class);
    }
}