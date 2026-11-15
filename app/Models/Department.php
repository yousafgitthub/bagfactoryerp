<?php

namespace App\Models;

use App\Models\Concerns\HasMasterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasMasterStatus;

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
    ];

    public function designations(): HasMany
    {
        return $this->hasMany(Designation::class);
    }
}