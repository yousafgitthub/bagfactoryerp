<?php

namespace App\Services\Bag;

use App\Models\BagBatchMaterialConsumption;
use App\Models\BagBatchOperator;
use App\Models\BagBillOfMaterial;
use App\Models\BagBomLine;
use App\Models\BagProductionBatch;
use App\Models\BagProductionOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BagBatchPersistenceService
{
    public function __construct(
        private readonly BagBatchNumberGenerator $numbers,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): BagProductionBatch
    {
        return DB::transaction(function () use ($data, $actor) {
            $order = BagProductionOrder::query()->findOrFail($data['bag_production_order_id']);

            if (in_array($order->status, ['draft', 'cancelled'], true)) {
                throw new BagBatchException('Production order must be released before creating batches.');
            }

            $bomId = $data['bag_bill_of_material_id'] ?? $order->bag_bill_of_material_id;
            $bomVersion = $order->bom_version_snapshot;
            if ($bomId) {
                $bomVersion = (int) BagBillOfMaterial::query()->whereKey($bomId)->value('version');
            }

            $batch = BagProductionBatch::query()->create([
                'batch_number' => $this->numbers->next(),
                'bag_production_order_id' => $order->id,
                'production_date' => $data['production_date'],
                'status' => 'draft',
                'machine_id' => $data['machine_id'] ?? $order->machine_id,
                'production_hall_id' => $data['production_hall_id'] ?? $order->production_hall_id,
                'bag_bill_of_material_id' => $bomId,
                'bom_version_snapshot' => $bomVersion,
                'good_quantity' => $data['good_quantity'] ?? 0,
                'rejected_quantity' => $data['rejected_quantity'] ?? 0,
                'dana_consumed_kg' => $data['dana_consumed_kg'] ?? 0,
                'color_material_consumed_kg' => $data['color_material_consumed_kg'] ?? 0,
                'actual_weight_per_good_bag_g' => $data['actual_weight_per_good_bag_g'] ?? null,
                'reusable_waste_kg' => $data['reusable_waste_kg'] ?? 0,
                'scrap_waste_kg' => $data['scrap_waste_kg'] ?? 0,
                'process_loss_kg' => $data['process_loss_kg'] ?? 0,
                'electricity_cost' => $data['electricity_cost'] ?? 0,
                'other_overhead_cost' => $data['other_overhead_cost'] ?? 0,
                'finished_goods_warehouse_id' => $data['finished_goods_warehouse_id'] ?? $order->finished_goods_warehouse_id,
                'dana_warehouse_id' => $data['dana_warehouse_id'] ?? $order->dana_warehouse_id,
                'materials_warehouse_id' => $data['materials_warehouse_id'] ?? $order->materials_warehouse_id,
                'rejected_warehouse_id' => $data['rejected_warehouse_id'] ?? $order->rejected_warehouse_id,
                'waste_warehouse_id' => $data['waste_warehouse_id'] ?? $order->waste_warehouse_id,
                'reusable_waste_material_id' => $data['reusable_waste_material_id'] ?? null,
                'scrap_waste_material_id' => $data['scrap_waste_material_id'] ?? null,
                'production_time_hours' => $data['production_time_hours'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->syncConsumptions($batch, $data['consumptions'] ?? []);
            $this->syncOperators($batch, $data['operators'] ?? []);

            if (empty($data['consumptions']) && $bomId) {
                $this->applyBomLines($batch, (int) $bomId);
            }

            if ($order->status === 'released') {
                app(BagOrderWorkflowService::class)->transition($order, 'start', $actor);
            }

            return $batch->fresh(['materialConsumptions', 'operators', 'order.variant']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BagProductionBatch $batch, array $data): BagProductionBatch
    {
        if (! $batch->isEditable()) {
            throw new BagBatchException('Batch cannot be edited in its current status.');
        }

        return DB::transaction(function () use ($batch, $data) {
            $batch->update([
                'production_date' => $data['production_date'],
                'machine_id' => $data['machine_id'],
                'production_hall_id' => $data['production_hall_id'],
                'bag_bill_of_material_id' => $data['bag_bill_of_material_id'] ?? null,
                'good_quantity' => $data['good_quantity'] ?? 0,
                'rejected_quantity' => $data['rejected_quantity'] ?? 0,
                'dana_consumed_kg' => $data['dana_consumed_kg'] ?? 0,
                'color_material_consumed_kg' => $data['color_material_consumed_kg'] ?? 0,
                'actual_weight_per_good_bag_g' => $data['actual_weight_per_good_bag_g'] ?? null,
                'reusable_waste_kg' => $data['reusable_waste_kg'] ?? 0,
                'scrap_waste_kg' => $data['scrap_waste_kg'] ?? 0,
                'process_loss_kg' => $data['process_loss_kg'] ?? 0,
                'electricity_cost' => $data['electricity_cost'] ?? 0,
                'other_overhead_cost' => $data['other_overhead_cost'] ?? 0,
                'finished_goods_warehouse_id' => $data['finished_goods_warehouse_id'],
                'dana_warehouse_id' => $data['dana_warehouse_id'],
                'materials_warehouse_id' => $data['materials_warehouse_id'],
                'rejected_warehouse_id' => $data['rejected_warehouse_id'] ?? null,
                'waste_warehouse_id' => $data['waste_warehouse_id'] ?? null,
                'reusable_waste_material_id' => $data['reusable_waste_material_id'] ?? null,
                'scrap_waste_material_id' => $data['scrap_waste_material_id'] ?? null,
                'production_time_hours' => $data['production_time_hours'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncConsumptions($batch, $data['consumptions'] ?? []);
            $this->syncOperators($batch, $data['operators'] ?? []);

            return $batch->fresh(['materialConsumptions', 'operators']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $consumptions
     */
    private function syncConsumptions(BagProductionBatch $batch, array $consumptions): void
    {
        BagBatchMaterialConsumption::query()->where('bag_production_batch_id', $batch->id)->delete();

        $order = 1;
        foreach ($consumptions as $row) {
            if (empty($row['material_id']) || (float) ($row['quantity'] ?? 0) <= 0) {
                continue;
            }

            BagBatchMaterialConsumption::query()->create([
                'bag_production_batch_id' => $batch->id,
                'material_id' => $row['material_id'],
                'material_variant_id' => $row['material_variant_id'] ?? null,
                'consumption_type' => $row['consumption_type'] ?? 'material',
                'quantity' => $row['quantity'],
                'unit' => $row['unit'] ?? 'kg',
                'line_order' => $order++,
            ]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $operators
     */
    private function syncOperators(BagProductionBatch $batch, array $operators): void
    {
        BagBatchOperator::query()->where('bag_production_batch_id', $batch->id)->delete();

        $order = 1;
        foreach ($operators as $row) {
            if (empty($row['operator_name'])) {
                continue;
            }

            BagBatchOperator::query()->create([
                'bag_production_batch_id' => $batch->id,
                'operator_name' => $row['operator_name'],
                'labor_cost' => $row['labor_cost'] ?? 0,
                'hours' => $row['hours'] ?? null,
                'line_order' => $order++,
            ]);
        }
    }

    private function applyBomLines(BagProductionBatch $batch, int $bomId): void
    {
        $bom = BagBillOfMaterial::query()->with('lines.material')->findOrFail($bomId);
        $totalBags = max((float) $batch->good_quantity + (float) $batch->rejected_quantity, 0);

        if ($totalBags <= 0) {
            return;
        }

        $order = 1;
        foreach ($bom->lines as $line) {
            $qty = $line->quantityForBags($totalBags);
            $type = 'material';
            $bucket = $line->material?->inventory_bucket ?? 'other';
            if ($bucket === 'dana') {
                $type = 'dana';
            }

            BagBatchMaterialConsumption::query()->create([
                'bag_production_batch_id' => $batch->id,
                'material_id' => $line->material_id,
                'material_variant_id' => $line->material_variant_id,
                'consumption_type' => $type,
                'quantity' => $qty,
                'unit' => 'kg',
                'line_order' => $order++,
            ]);
        }

        $batch->load('materialConsumptions');
    }
}