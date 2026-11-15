<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DanaBatch extends Model
{
    protected $fillable = [
        'batch_number',
        'production_date',
        'status',
        'machine_id',
        'production_hall_id',
        'raw_material_warehouse_id',
        'finished_dana_warehouse_id',
        'waste_warehouse_id',
        'output_material_id',
        'output_material_variant_id',
        'dana_production_recipe_id',
        'total_input_kg',
        'output_kg',
        'reusable_waste_kg',
        'scrap_waste_kg',
        'process_loss_kg',
        'electricity_cost',
        'other_overhead_cost',
        'direct_labor_cost',
        'raw_material_cost',
        'color_material_cost',
        'total_batch_cost',
        'cost_per_kg',
        'reusable_waste_material_id',
        'scrap_waste_material_id',
        'notes',
        'posted_at',
        'posted_by',
        'approved_at',
        'approved_by',
        'cancelled_at',
        'cancelled_by',
        'reversal_of_batch_id',
        'reversed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'total_input_kg' => 'decimal:4',
            'output_kg' => 'decimal:4',
            'reusable_waste_kg' => 'decimal:4',
            'scrap_waste_kg' => 'decimal:4',
            'process_loss_kg' => 'decimal:4',
            'electricity_cost' => 'decimal:4',
            'other_overhead_cost' => 'decimal:4',
            'direct_labor_cost' => 'decimal:4',
            'raw_material_cost' => 'decimal:4',
            'color_material_cost' => 'decimal:4',
            'total_batch_cost' => 'decimal:4',
            'cost_per_kg' => 'decimal:4',
            'posted_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function productionHall(): BelongsTo
    {
        return $this->belongsTo(ProductionHall::class);
    }

    public function rawWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'raw_material_warehouse_id');
    }

    public function finishedWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'finished_dana_warehouse_id');
    }

    public function wasteWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'waste_warehouse_id');
    }

    public function outputMaterial(): BelongsTo
    {
        return $this->belongsTo(Material::class, 'output_material_id');
    }

    public function outputVariant(): BelongsTo
    {
        return $this->belongsTo(MaterialVariant::class, 'output_material_variant_id');
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(DanaProductionRecipe::class, 'dana_production_recipe_id');
    }

    public function materialInputs(): HasMany
    {
        return $this->hasMany(DanaBatchMaterialInput::class)->orderBy('line_order');
    }

    public function operators(): HasMany
    {
        return $this->hasMany(DanaBatchOperator::class)->orderBy('line_order');
    }

    public function wastes(): HasMany
    {
        return $this->hasMany(DanaBatchWaste::class);
    }

    public function inventoryLinks(): HasMany
    {
        return $this->hasMany(DanaBatchInventoryLink::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPosted(): bool
    {
        return $this->status === 'posted' && $this->posted_at !== null;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'submitted'], true);
    }
}