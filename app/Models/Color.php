<?php

namespace App\Models;

use App\Models\Concerns\HasMasterStatus;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasMasterStatus;

    protected $fillable = [
        'name',
        'code',
        'hex_color',
        'reference_color',
        'description',
        'status',
    ];
}