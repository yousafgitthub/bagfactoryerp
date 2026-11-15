<?php

namespace App\Services\Bag;

use App\Models\BagProductionBatch;
use App\Models\InventoryBalance;

class BagConsumptionPreviewService
{
    public function __construct(
        private readonly BagBatchPostingService $posting,
    ) {
    }

    /**
     * @return array{
     *     costs: array<string, float>,
     *     consumptions: list<array<string, mixed>>,
     *     stock_checks: list<array<string, mixed>>,
     *     mass_balance: array<string, mixed>,
     * }
     */
    public function preview(BagProductionBatch $batch): array
    {
        $batch->loadMissing(['materialConsumptions.material', 'operators', 'order.variant.product']);

        $costs = $this->posting->computeCosts($batch);

        $consumptions = [];
        foreach ($batch->materialConsumptions as $line) {
            $consumptions[] = [
                'material_id' => $line->material_id,
                'material_name' => $line->material?->name,
                'consumption_type' => $line->consumption_type,
                'quantity' => (float) $line->quantity,
                'unit' => $line->unit,
                'estimated_cost' => round((float) $line->quantity * (float) $line->unit_cost_snapshot, 4),
            ];
        }

        if ((float) $batch->dana_consumed_kg > 0) {
            $consumptions[] = [
                'material_id' => null,
                'material_name' => 'Dana (header)',
                'consumption_type' => 'dana',
                'quantity' => (float) $batch->dana_consumed_kg,
                'unit' => 'kg',
                'estimated_cost' => (float) $costs['dana_cost'],
            ];
        }

        $stockChecks = $this->stockAvailability($batch);

        return [
            'costs' => $costs,
            'consumptions' => $consumptions,
            'stock_checks' => $stockChecks,
            'mass_balance' => $this->posting->massBalanceSummary($batch),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function stockAvailability(BagProductionBatch $batch): array
    {
        $checks = [];

        foreach ($batch->materialConsumptions as $line) {
            $warehouseId = $line->consumption_type === 'dana'
                ? $batch->dana_warehouse_id
                : $batch->materials_warehouse_id;

            $available = $this->availableQty(
                (int) $warehouseId,
                (int) $line->material_id,
                $line->material_variant_id ? (int) $line->material_variant_id : null,
            );

            $checks[] = [
                'material' => $line->material?->name,
                'required' => (float) $line->quantity,
                'available' => $available,
                'sufficient' => $available + 0.0001 >= (float) $line->quantity,
            ];
        }

        return $checks;
    }

    private function availableQty(int $warehouseId, int $materialId, ?int $variantId): float
    {
        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('material_id', $materialId)
            ->when(
                $variantId,
                fn ($q) => $q->where('material_variant_id', $variantId),
                fn ($q) => $q->whereNull('material_variant_id'),
            )
            ->first();

        return (float) ($balance?->quantity ?? 0);
    }
}