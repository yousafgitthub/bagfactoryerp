<?php

namespace App\Models;

use App\Models\Concerns\HasMasterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitOfMeasurement extends Model
{
    use HasMasterStatus;

    protected $table = 'units_of_measurement';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'description',
        'status',
    ];

    public function machinesWithCapacity(): HasMany
    {
        return $this->hasMany(Machine::class, 'capacity_unit_id');
    }
}