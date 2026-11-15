<?php

namespace App\Models;

use App\Models\Concerns\HasMasterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Machine extends Model
{
    use HasMasterStatus;

    protected $fillable = [
        'code',
        'name',
        'machine_type_id',
        'production_hall_id',
        'capacity',
        'capacity_unit_id',
        'status',
        'installation_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
            'capacity' => 'decimal:3',
        ];
    }

    public function machineType(): BelongsTo
    {
        return $this->belongsTo(MachineType::class);
    }

    public function productionHall(): BelongsTo
    {
        return $this->belongsTo(ProductionHall::class);
    }

    public function capacityUnit(): BelongsTo
    {
        return $this->belongsTo(UnitOfMeasurement::class, 'capacity_unit_id');
    }
}