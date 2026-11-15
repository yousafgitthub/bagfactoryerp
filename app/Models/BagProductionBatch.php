<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BagProductionBatch extends Model
{
    protected $fillable = [
        'batch_number',
        'bag_production_order_id',
        'production_date',
        'status',
        'machine_id',
        'production_hall_id',
        'bag_bill_of_material_id',
        'bom_version_snapshot',
        'good_quantity',
        'rejected_quantity',
        'dana_consumed_kg',
        'color_material_consumed_kg',
        'actual_weight_per_good_bag_g',
        'reusable_waste_kg',
        'scrap_waste_kg',
        'process_loss_kg',
        'electricity_cost',
        'other_overhead_cost',
        'direct_labor_cost',
        'material_cost',
        'dana_cost',
        'color_material_cost',
        'total_batch_cost',
        'cost_per_good_bag',
        'finished_goods_warehouse_id',
        'dana_warehouse_id',
        'materials_warehouse_id',
        'rejected_warehouse_id',
        'waste_warehouse_id',
        'reusable_waste_material_id',
        'scrap_waste_material_id',
        'production_time_hours',
        'notes',
        'posted_at',
        'posted_by',
        'approved_at',
        'approved_by',
        'cancelled_at',
        'cancelled_by',
        'reversed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'good_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'dana_consumed_kg' => 'decimal:4',
            'color_material_consumed_kg' => 'decimal:4',
            'actual_weight_per_good_bag_g' => 'decimal:4',
            'reusable_waste_kg' => 'decimal:4',
            'scrap_waste_kg' => 'decimal:4',
            'process_loss_kg' => 'decimal:4',
            'electricity_cost' => 'decimal:4',
            'other_overhead_cost' => 'decimal:4',
            'direct_labor_cost' => 'decimal:4',
            'material_cost' => 'decimal:4',
            'dana_cost' => 'decimal:4',
            'color_material_cost' => 'decimal:4',
            'total_batch_cost' => 'decimal:4',
            'cost_per_good_bag' => 'decimal:4',
            'production_time_hours' => 'decimal:2',
            'posted_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(BagProductionOrder::class, 'bag_production_order_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function productionHall(): BelongsTo
    {
        return $this->belongsTo(ProductionHall::class);
    }

    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BagBillOfMaterial::class, 'bag_bill_of_material_id');
    }

    public function materialConsumptions(): HasMany
    {
        return $this->hasMany(BagBatchMaterialConsumption::class)->orderBy('line_order');
    }

    public function operators(): HasMany
    {
        return $this->hasMany(BagBatchOperator::class)->orderBy('line_order');
    }

    public function wastes(): HasMany
    {
        return $this->hasMany(BagBatchWaste::class);
    }

    public function inventoryLinks(): HasMany
    {
        return $this->hasMany(BagBatchInventoryLink::class);
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

    public function totalBagsProduced(): float
    {
        return (float) $this->good_quantity + (float) $this->rejected_quantity;
    }
}