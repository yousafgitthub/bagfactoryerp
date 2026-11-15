<?php

namespace App\Services\Bag;

use App\Models\BagBillOfMaterial;
use App\Models\BagProductionOrder;
use App\Models\BagProductionOrderAssignment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BagOrderPersistenceService
{
    public function __construct(
        private readonly BagOrderNumberGenerator $numbers,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): BagProductionOrder
    {
        return DB::transaction(function () use ($data, $actor) {
            $bomId = $data['bag_bill_of_material_id'] ?? null;
            $bomVersion = null;
            if ($bomId) {
                $bomVersion = (int) BagBillOfMaterial::query()->whereKey($bomId)->value('version');
            }

            $order = BagProductionOrder::query()->create([
                'order_number' => $this->numbers->next(),
                'status' => 'draft',
                'bag_product_id' => $data['bag_product_id'],
                'bag_product_variant_id' => $data['bag_product_variant_id'],
                'bag_bill_of_material_id' => $bomId,
                'bom_version_snapshot' => $bomVersion,
                'planned_quantity' => $data['planned_quantity'],
                'machine_id' => $data['machine_id'] ?? null,
                'production_hall_id' => $data['production_hall_id'] ?? null,
                'color_id' => $data['color_id'] ?? null,
                'planned_start_date' => $data['planned_start_date'] ?? null,
                'planned_end_date' => $data['planned_end_date'] ?? null,
                'finished_goods_warehouse_id' => $data['finished_goods_warehouse_id'],
                'dana_warehouse_id' => $data['dana_warehouse_id'],
                'materials_warehouse_id' => $data['materials_warehouse_id'],
                'rejected_warehouse_id' => $data['rejected_warehouse_id'] ?? null,
                'waste_warehouse_id' => $data['waste_warehouse_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->syncAssignments($order, $data['assignments'] ?? []);

            return $order->fresh(['assignments', 'product', 'variant']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BagProductionOrder $order, array $data): BagProductionOrder
    {
        if (! $order->isEditable()) {
            throw new BagBatchException('Order cannot be edited in its current status.');
        }

        return DB::transaction(function () use ($order, $data) {
            $bomId = $data['bag_bill_of_material_id'] ?? null;
            $bomVersion = $order->bom_version_snapshot;
            if ($bomId && $bomId !== $order->bag_bill_of_material_id) {
                $bomVersion = (int) BagBillOfMaterial::query()->whereKey($bomId)->value('version');
            }

            $order->update([
                'bag_product_id' => $data['bag_product_id'],
                'bag_product_variant_id' => $data['bag_product_variant_id'],
                'bag_bill_of_material_id' => $bomId,
                'bom_version_snapshot' => $bomVersion,
                'planned_quantity' => $data['planned_quantity'],
                'machine_id' => $data['machine_id'] ?? null,
                'production_hall_id' => $data['production_hall_id'] ?? null,
                'color_id' => $data['color_id'] ?? null,
                'planned_start_date' => $data['planned_start_date'] ?? null,
                'planned_end_date' => $data['planned_end_date'] ?? null,
                'finished_goods_warehouse_id' => $data['finished_goods_warehouse_id'],
                'dana_warehouse_id' => $data['dana_warehouse_id'],
                'materials_warehouse_id' => $data['materials_warehouse_id'],
                'rejected_warehouse_id' => $data['rejected_warehouse_id'] ?? null,
                'waste_warehouse_id' => $data['waste_warehouse_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncAssignments($order, $data['assignments'] ?? []);

            return $order->fresh(['assignments', 'product', 'variant']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $assignments
     */
    private function syncAssignments(BagProductionOrder $order, array $assignments): void
    {
        BagProductionOrderAssignment::query()->where('bag_production_order_id', $order->id)->delete();

        $line = 1;
        foreach ($assignments as $row) {
            if (empty($row['employee_name'])) {
                continue;
            }

            BagProductionOrderAssignment::query()->create([
                'bag_production_order_id' => $order->id,
                'employee_name' => $row['employee_name'],
                'role' => $row['role'] ?? null,
                'line_order' => $line++,
            ]);
        }
    }

    public function refreshProducedTotals(BagProductionOrder $order): void
    {
        $order->loadMissing('batches');

        $good = 0.0;
        $rejected = 0.0;

        foreach ($order->batches as $batch) {
            if ($batch->status === 'posted') {
                $good += (float) $batch->good_quantity;
                $rejected += (float) $batch->rejected_quantity;
            }
        }

        $order->update([
            'produced_quantity' => $good,
            'rejected_quantity' => $rejected,
        ]);
    }
}