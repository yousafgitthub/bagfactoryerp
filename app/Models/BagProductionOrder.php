<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BagProductionOrder extends Model
{
    protected $fillable = [
        'order_number',
        'status',
        'bag_product_id',
        'bag_product_variant_id',
        'bag_bill_of_material_id',
        'bom_version_snapshot',
        'planned_quantity',
        'produced_quantity',
        'rejected_quantity',
        'machine_id',
        'production_hall_id',
        'color_id',
        'planned_start_date',
        'planned_end_date',
        'finished_goods_warehouse_id',
        'dana_warehouse_id',
        'materials_warehouse_id',
        'rejected_warehouse_id',
        'waste_warehouse_id',
        'notes',
        'released_at',
        'released_by',
        'cancelled_at',
        'cancelled_by',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'planned_quantity' => 'decimal:4',
            'produced_quantity' => 'decimal:4',
            'rejected_quantity' => 'decimal:4',
            'planned_start_date' => 'date',
            'planned_end_date' => 'date',
            'released_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(BagProduct::class, 'bag_product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(BagProductVariant::class, 'bag_product_variant_id');
    }

    public function billOfMaterial(): BelongsTo
    {
        return $this->belongsTo(BagBillOfMaterial::class, 'bag_bill_of_material_id');
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function productionHall(): BelongsTo
    {
        return $this->belongsTo(ProductionHall::class);
    }

    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    public function finishedGoodsWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'finished_goods_warehouse_id');
    }

    public function danaWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'dana_warehouse_id');
    }

    public function materialsWarehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'materials_warehouse_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(BagProductionOrderAssignment::class)->orderBy('line_order');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(BagProductionBatch::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isEditable(): bool
    {
        return $this->status === 'draft';
    }

    public function remainingQuantity(): float
    {
        return max((float) $this->planned_quantity - (float) $this->produced_quantity, 0);
    }
}