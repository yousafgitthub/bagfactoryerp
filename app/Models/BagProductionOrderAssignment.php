<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BagProductionOrderAssignment extends Model
{
    protected $fillable = [
        'bag_production_order_id',
        'employee_name',
        'role',
        'line_order',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(BagProductionOrder::class, 'bag_production_order_id');
    }
}