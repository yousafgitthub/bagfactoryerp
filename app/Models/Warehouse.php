<?php

namespace App\Models;

use App\Models\Concerns\HasMasterStatus;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasMasterStatus;

    protected $fillable = [
        'code',
        'name',
        'address',
        'description',
        'status',
    ];
}