<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DanaProductionMachineSetting extends Model
{
    protected $fillable = [
        'machine_id',
        'max_output_kg_per_batch',
        'max_process_loss_percent',
    ];

    protected function casts(): array
    {
        return [
            'max_output_kg_per_batch' => 'decimal:4',
            'max_process_loss_percent' => 'decimal:4',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }
}