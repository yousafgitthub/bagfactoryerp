<?php

namespace App\Models;

use App\Models\Concerns\HasMasterStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Designation extends Model
{
    use HasMasterStatus;

    protected $fillable = [
        'code',
        'name',
        'department_id',
        'description',
        'status',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}