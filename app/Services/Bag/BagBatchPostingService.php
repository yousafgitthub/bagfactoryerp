<?php

namespace App\Services\Bag;

use App\Models\BagBatchInventoryLink;
use App\Models\BagBatchMaterialConsumption;
use App\Models\BagBatchWaste;
use App\Models\BagProductionBatch;
use App\Models\BagProductionMachineSetting;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\User;
use App\Services\Inventory\InventoryPostingException;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use Illuminate\Support\Facades\DB;

class BagBatchPostingService
{
    public function __construct(
        private readonly InventoryPostingService $inventoryPosting,
        private readonly InventoryTransactionNumberGenerator $numbers,
        private readonly BagBatchReversalService $reversal,
        private readonly BagOrderPersistenceService $orders,
    ) {
    }

    public function post(BagProductionBatch $batch, User $actor): BagProductionBatch
    {
        return DB::transaction(function () use ($batch, $actor) {
            $locked = BagProductionBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->with(['materialConsumptions', 'operators', 'order.variant'])
                ->firstOrFail();

            if ($locked->isPosted()) {
                throw new BagBatchException('Batch is already posted.');
            }

            if ($locked->status !== 'completed') {
                throw new BagBatchException('Only completed batches can be posted.');
            }

            if ($locked->inventoryLinks()->exists()) {
                throw new BagBatchException('Batch already has inventory postings.');
            }

            if ($locked->order->status === 'cancelled') {
                throw new BagBatchException('Cannot post a batch for a cancelled order.');
            }

            $this->validateQuantities($locked);
            $this->validateMassBalance($locked);
            $this->validateWeightTolerance($locked);
            $this->validateProcessLoss($locked);
            $this->validateMachineCapacity($locked);
            $this->validateOrderRemaining($locked);

            $this->snapshotCosts($locked);
            $costs = $this->computeCosts($locked);

            $occurredDate = $locked->production_date;

            $this->postMaterialIssues($locked, $actor, $occurredDate);
            $this->postDanaIssue($locked, $actor, $occurredDate, $costs);
            $this->postGoodOutput($locked, $actor, $occurredDate, $costs);
            $this->postRejectedOutput($locked, $actor, $occurredDate, $costs);

            $this->postWasteStock(
                batch: $locked,
                actor: $actor,
                occurredDate: $occurredDate,
                quantity: (float) $locked->reusable_waste_kg,
                materialId: $locked->reusable_waste_material_id,
                wasteType: 'reusable',
            );

            $this->postWasteStock(
                batch: $locked,
                actor: $actor,
                occurredDate: $occurredDate,
                quantity: (float) $locked->scrap_waste_kg,
                materialId: $locked->scrap_waste_material_id,
                wasteType: 'scrap',
            );

            $this->recordProcessLoss($locked);

            $locked->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actor->id,
                'material_cost' => $costs['material_cost'],
                'dana_cost' => $costs['dana_cost'],
                'color_material_cost' => $costs['color_material_cost'],
                'direct_labor_cost' => $costs['direct_labor_cost'],
                'total_batch_cost' => $costs['total_batch_cost'],
                'cost_per_good_bag' => $costs['cost_per_good_bag'],
            ]);

            $this->orders->refreshProducedTotals($locked->order);

            if (
                (float) $locked->order->produced_quantity >= (float) $locked->order->planned_quantity
                && $locked->order->status === 'in_progress'
            ) {
                app(BagOrderWorkflowService::class)->transition($locked->order, 'complete', $actor);
            }

            $this->recordAccountingHook($locked);

            return $locked->fresh([
                'materialConsumptions.material',
                'operators',
                'inventoryLinks.inventoryTransaction',
                'order',
            ]);
        });
    }

    public function reverse(BagProductionBatch $batch, User $actor, ?string $notes = null): BagProductionBatch
    {
        return $this->reversal->reverse($batch, $actor, $notes);
    }

    /**
     * @return array{
     *     material_cost: float,
     *     dana_cost: float,
     *     color_material_cost: float,
     *     direct_labor_cost: float,
     *     total_batch_cost: float,
     *     cost_per_good_bag: float,
     * }
     */
    public function computeCosts(BagProductionBatch $batch): array
    {
        $batch->loadMissing(['materialConsumptions.material']);

        $dana = 0.0;
        $color = 0.0;
        $material = 0.0;

        foreach ($batch->materialConsumptions as $line) {
            $unitCost = (float) $line->unit_cost_snapshot > 0
                ? (float) $line->unit_cost_snapshot
                : $this->resolveUnitCost($batch, $line);
            $value = (float) $line->quantity * $unitCost;

            match ($line->consumption_type) {
                'dana' => $dana += $value,
                'color' => $color += $value,
                default => $material += $value,
            };
        }

        if ((float) $batch->dana_consumed_kg > 0) {
            $danaMaterialCost = $this->resolveDanaHeaderCost($batch);
            $dana += $danaMaterialCost;
        }

        if ((float) $batch->color_material_consumed_kg > 0) {
            $color += $this->resolveColorHeaderCost($batch);
        }

        $labor = (float) $batch->operators->sum('labor_cost');
        $electricity = (float) $batch->electricity_cost;
        $overhead = (float) $batch->other_overhead_cost;
        $total = $dana + $color + $material + $labor + $electricity + $overhead;
        $good = max((float) $batch->good_quantity, 0.0001);
        $costPerBag = round($total / $good, 4);

        return [
            'material_cost' => round($material, 4),
            'dana_cost' => round($dana, 4),
            'color_material_cost' => round($color, 4),
            'direct_labor_cost' => round($labor, 4),
            'total_batch_cost' => round($total, 4),
            'cost_per_good_bag' => $costPerBag,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function massBalanceSummary(BagProductionBatch $batch): array
    {
        $weightG = $this->effectiveWeightPerBagG($batch);
        $goodKg = ((float) $batch->good_quantity * $weightG) / 1000;
        $rejectedKg = ((float) $batch->rejected_quantity * $weightG) / 1000;

        $danaFromLines = (float) $batch->materialConsumptions
            ->where('consumption_type', 'dana')
            ->sum('quantity');
        $danaKg = (float) $batch->dana_consumed_kg > 0
            ? (float) $batch->dana_consumed_kg
            : $danaFromLines;

        $otherKg = (float) $batch->materialConsumptions
            ->whereNotIn('consumption_type', ['dana'])
            ->sum('quantity');

        $consumptionKg = $danaKg
            + (float) $batch->color_material_consumed_kg
            + $otherKg;

        $outputKg = $goodKg + $rejectedKg
            + (float) $batch->reusable_waste_kg
            + (float) $batch->scrap_waste_kg
            + (float) $batch->process_loss_kg;

        return [
            'consumption_kg' => round($consumptionKg, 4),
            'output_kg' => round($outputKg, 4),
            'delta_kg' => round($consumptionKg - $outputKg, 4),
            'weight_per_bag_g' => $weightG,
        ];
    }

    private function validateQuantities(BagProductionBatch $batch): void
    {
        if ((float) $batch->good_quantity <= 0 && (float) $batch->rejected_quantity <= 0) {
            throw new BagBatchException('At least one good or rejected bag quantity is required.');
        }
    }

    private function validateMassBalance(BagProductionBatch $batch): void
    {
        $summary = $this->massBalanceSummary($batch);
        $tolerance = (float) config('bag.mass_balance_tolerance_kg', 0.05);

        if (abs((float) $summary['delta_kg']) > $tolerance) {
            throw new BagBatchException(sprintf(
                'Mass balance mismatch: consumption %.4f KG vs output+waste %.4f KG (delta %.4f).',
                (float) $summary['consumption_kg'],
                (float) $summary['output_kg'],
                (float) $summary['delta_kg'],
            ));
        }
    }

    private function validateWeightTolerance(BagProductionBatch $batch): void
    {
        $variant = $batch->order?->variant;
        if (! $variant) {
            return;
        }

        $nominal = $variant->effectiveWeightPerBagG();
        $actual = (float) ($batch->actual_weight_per_good_bag_g ?? 0);

        if ($nominal <= 0 || $actual <= 0) {
            return;
        }

        $pct = abs($actual - $nominal) / $nominal * 100;
        $max = (float) config('bag.weight_tolerance_percent', 15);

        if ($pct > $max) {
            throw new BagBatchException(sprintf(
                'Actual bag weight %.2fg deviates %.1f%% from nominal %.2fg (max %.1f%%).',
                $actual,
                $pct,
                $nominal,
                $max,
            ));
        }
    }

    private function validateProcessLoss(BagProductionBatch $batch): void
    {
        $inputKg = (float) $batch->dana_consumed_kg
            + (float) $batch->color_material_consumed_kg
            + (float) $batch->materialConsumptions->sum('quantity');

        if ($inputKg <= 0) {
            return;
        }

        $lossPct = ((float) $batch->process_loss_kg / $inputKg) * 100;
        $maxPct = $this->maxProcessLossPercent($batch);

        if ($lossPct > $maxPct + 0.0001) {
            throw new BagBatchException(sprintf(
                'Process loss %.2f%% exceeds allowed maximum %.2f%%.',
                $lossPct,
                $maxPct,
            ));
        }
    }

    private function maxProcessLossPercent(BagProductionBatch $batch): float
    {
        $setting = BagProductionMachineSetting::query()
            ->where('machine_id', $batch->machine_id)
            ->first();

        if ($setting?->max_process_loss_percent !== null) {
            return (float) $setting->max_process_loss_percent;
        }

        return (float) config('bag.default_max_process_loss_percent', 5);
    }

    private function validateMachineCapacity(BagProductionBatch $batch): void
    {
        $totalBags = $batch->totalBagsProduced();
        $setting = BagProductionMachineSetting::query()
            ->where('machine_id', $batch->machine_id)
            ->first();

        if ($setting?->max_bags_per_batch !== null && $totalBags > (float) $setting->max_bags_per_batch) {
            throw new BagBatchException('Batch bag count exceeds machine batch limit.');
        }
    }

    private function validateOrderRemaining(BagProductionBatch $batch): void
    {
        $order = $batch->order;
        $remaining = $order->remainingQuantity();

        if ((float) $batch->good_quantity > $remaining + 0.0001) {
            throw new BagBatchException(sprintf(
                'Good quantity %.0f exceeds order remaining %.0f.',
                (float) $batch->good_quantity,
                $remaining,
            ));
        }
    }

    private function snapshotCosts(BagProductionBatch $batch): void
    {
        foreach ($batch->materialConsumptions as $line) {
            $line->update([
                'unit_cost_snapshot' => $this->resolveUnitCost($batch, $line),
            ]);
        }

        $batch->load('materialConsumptions');
    }

    private function resolveUnitCost(BagProductionBatch $batch, BagBatchMaterialConsumption $line): float
    {
        $warehouseId = $line->consumption_type === 'dana'
            ? $batch->dana_warehouse_id
            : $batch->materials_warehouse_id;

        $balance = InventoryBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('material_id', $line->material_id)
            ->when(
                $line->material_variant_id,
                fn ($q) => $q->where('material_variant_id', $line->material_variant_id),
                fn ($q) => $q->whereNull('material_variant_id'),
            )
            ->first();

        if ($balance && (float) $balance->average_unit_cost > 0) {
            return (float) $balance->average_unit_cost;
        }

        return (float) ($line->material?->default_unit_cost ?? 0);
    }

    private function resolveDanaHeaderCost(BagProductionBatch $batch): float
    {
        $danaLine = $batch->materialConsumptions->firstWhere('consumption_type', 'dana');
        if ($danaLine) {
            return 0;
        }

        $danaMaterial = $batch->materialConsumptions
            ->first(fn ($l) => $l->material?->inventory_bucket === 'dana');

        if ($danaMaterial) {
            $unit = (float) $danaMaterial->unit_cost_snapshot ?: $this->resolveUnitCost($batch, $danaMaterial);

            return round((float) $batch->dana_consumed_kg * $unit, 4);
        }

        $balance = InventoryBalance::query()
            ->where('warehouse_id', $batch->dana_warehouse_id)
            ->whereHas('material', fn ($q) => $q->where('inventory_bucket', 'dana'))
            ->orderByDesc('quantity')
            ->first();

        $unit = (float) ($balance?->average_unit_cost ?? 0);

        return round((float) $batch->dana_consumed_kg * $unit, 4);
    }

    private function resolveColorHeaderCost(BagProductionBatch $batch): float
    {
        $colorLine = $batch->materialConsumptions->firstWhere('consumption_type', 'color');
        if ($colorLine) {
            return 0;
        }

        return 0.0;
    }

    private function postMaterialIssues(BagProductionBatch $batch, User $actor, $occurredDate): void
    {
        $lines = $batch->materialConsumptions
            ->filter(fn ($l) => ! in_array($l->consumption_type, ['dana'], true) && (float) $l->quantity > 0);

        if ($lines->isEmpty()) {
            return;
        }

        $tx = InventoryTransaction::query()->create([
            'transaction_number' => $this->numbers->next('issue_to_production'),
            'type' => 'issue_to_production',
            'status' => 'draft',
            'warehouse_id' => $batch->materials_warehouse_id,
            'transaction_date' => $occurredDate,
            'reference_type' => BagProductionBatch::class,
            'reference_id' => $batch->id,
            'notes' => 'Bag batch '.$batch->batch_number.' material consumption',
            'created_by' => $actor->id,
        ]);

        $order = 1;
        foreach ($lines as $line) {
            $unitCost = (float) $line->unit_cost_snapshot ?: $this->resolveUnitCost($batch, $line);
            InventoryTransactionItem::query()->create([
                'inventory_transaction_id' => $tx->id,
                'material_id' => $line->material_id,
                'material_variant_id' => $line->material_variant_id,
                'quantity' => $line->quantity,
                'unit_cost' => $unitCost,
                'total_value' => round((float) $line->quantity * $unitCost, 4),
                'line_order' => $order++,
            ]);
        }

        try {
            $this->inventoryPosting->post($tx, $actor);
        } catch (InventoryPostingException $e) {
            throw new BagBatchException($e->getMessage(), 0, $e);
        }

        $this->link($batch, $tx, 'material_consumption');
    }

    private function postDanaIssue(BagProductionBatch $batch, User $actor, $occurredDate, array $costs): void
    {
        $danaLines = $batch->materialConsumptions->where('consumption_type', 'dana');
        $headerKg = (float) $batch->dana_consumed_kg;

        if ($danaLines->isEmpty() && $headerKg <= 0) {
            return;
        }

        $tx = InventoryTransaction::query()->create([
            'transaction_number' => $this->numbers->next('issue_to_production'),
            'type' => 'issue_to_production',
            'status' => 'draft',
            'warehouse_id' => $batch->dana_warehouse_id,
            'transaction_date' => $occurredDate,
            'reference_type' => BagProductionBatch::class,
            'reference_id' => $batch->id,
            'notes' => 'Bag batch '.$batch->batch_number.' Dana consumption',
            'created_by' => $actor->id,
        ]);

        $order = 1;
        foreach ($danaLines as $line) {
            $unitCost = (float) $line->unit_cost_snapshot ?: $this->resolveUnitCost($batch, $line);
            InventoryTransactionItem::query()->create([
                'inventory_transaction_id' => $tx->id,
                'material_id' => $line->material_id,
                'material_variant_id' => $line->material_variant_id,
                'quantity' => $line->quantity,
                'unit_cost' => $unitCost,
                'total_value' => round((float) $line->quantity * $unitCost, 4),
                'line_order' => $order++,
            ]);
        }

        if ($headerKg > 0 && $danaLines->isEmpty()) {
            $balance = InventoryBalance::query()
                ->where('warehouse_id', $batch->dana_warehouse_id)
                ->whereHas('material', fn ($q) => $q->where('inventory_bucket', 'dana'))
                ->orderByDesc('quantity')
                ->first();

            if (! $balance) {
                throw new BagBatchException('No Dana stock available in the configured warehouse.');
            }

            $unitCost = (float) $balance->average_unit_cost;
            InventoryTransactionItem::query()->create([
                'inventory_transaction_id' => $tx->id,
                'material_id' => $balance->material_id,
                'material_variant_id' => $balance->material_variant_id,
                'quantity' => $headerKg,
                'unit_cost' => $unitCost,
                'total_value' => round($headerKg * $unitCost, 4),
                'line_order' => $order,
            ]);
        }

        try {
            $this->inventoryPosting->post($tx, $actor);
        } catch (InventoryPostingException $e) {
            throw new BagBatchException($e->getMessage(), 0, $e);
        }

        $this->link($batch, $tx, 'dana_consumption');
    }

    private function postGoodOutput(BagProductionBatch $batch, User $actor, $occurredDate, array $costs): void
    {
        $qty = (float) $batch->good_quantity;
        if ($qty <= 0) {
            return;
        }

        $variant = $batch->order->variant;
        $materialId = $variant?->saleable_material_id;
        $materialVariantId = $variant?->saleable_material_variant_id;

        if (! $materialId) {
            throw new BagBatchException('Saleable inventory material is not configured on the product variant.');
        }

        $tx = InventoryTransaction::query()->create([
            'transaction_number' => $this->numbers->next('production_receipt'),
            'type' => 'production_receipt',
            'status' => 'draft',
            'warehouse_id' => $batch->finished_goods_warehouse_id,
            'transaction_date' => $occurredDate,
            'reference_type' => BagProductionBatch::class,
            'reference_id' => $batch->id,
            'notes' => 'Bag batch '.$batch->batch_number.' — saleable output',
            'created_by' => $actor->id,
        ]);

        InventoryTransactionItem::query()->create([
            'inventory_transaction_id' => $tx->id,
            'material_id' => $materialId,
            'material_variant_id' => $materialVariantId,
            'quantity' => $qty,
            'unit_cost' => $costs['cost_per_good_bag'],
            'total_value' => round($qty * $costs['cost_per_good_bag'], 4),
            'line_order' => 1,
        ]);

        try {
            $this->inventoryPosting->post($tx, $actor);
        } catch (InventoryPostingException $e) {
            throw new BagBatchException($e->getMessage(), 0, $e);
        }

        $this->link($batch, $tx, 'good_output');
    }

    private function postRejectedOutput(BagProductionBatch $batch, User $actor, $occurredDate, array $costs): void
    {
        $qty = (float) $batch->rejected_quantity;
        if ($qty <= 0) {
            return;
        }

        $variant = $batch->order->variant;
        $materialId = $variant?->rejected_material_id;
        $materialVariantId = $variant?->rejected_material_variant_id;
        $warehouseId = $batch->rejected_warehouse_id ?? $batch->finished_goods_warehouse_id;

        if (! $materialId) {
            throw new BagBatchException('Rejected inventory material is not configured on the product variant.');
        }

        $rejectedUnitCost = 0.0;

        $tx = InventoryTransaction::query()->create([
            'transaction_number' => $this->numbers->next('production_receipt'),
            'type' => 'production_receipt',
            'status' => 'draft',
            'warehouse_id' => $warehouseId,
            'transaction_date' => $occurredDate,
            'reference_type' => BagProductionBatch::class,
            'reference_id' => $batch->id,
            'notes' => 'Bag batch '.$batch->batch_number.' — rejected (non-saleable)',
            'created_by' => $actor->id,
        ]);

        InventoryTransactionItem::query()->create([
            'inventory_transaction_id' => $tx->id,
            'material_id' => $materialId,
            'material_variant_id' => $materialVariantId,
            'quantity' => $qty,
            'unit_cost' => $rejectedUnitCost,
            'total_value' => 0,
            'line_order' => 1,
        ]);

        try {
            $this->inventoryPosting->post($tx, $actor);
        } catch (InventoryPostingException $e) {
            throw new BagBatchException($e->getMessage(), 0, $e);
        }

        $this->link($batch, $tx, 'rejected_output');
    }

    private function postWasteStock(
        BagProductionBatch $batch,
        User $actor,
        $occurredDate,
        float $quantity,
        ?int $materialId,
        string $wasteType,
    ): void {
        if ($quantity <= 0 || $materialId === null) {
            return;
        }

        $warehouseId = $batch->waste_warehouse_id;
        if ($warehouseId === null) {
            throw new BagBatchException('Waste warehouse is required when recording '.$wasteType.' waste stock.');
        }

        $tx = InventoryTransaction::query()->create([
            'transaction_number' => $this->numbers->next('waste_receipt'),
            'type' => 'waste_receipt',
            'status' => 'draft',
            'warehouse_id' => $warehouseId,
            'transaction_date' => $occurredDate,
            'reference_type' => BagProductionBatch::class,
            'reference_id' => $batch->id,
            'notes' => sprintf('Bag batch %s — %s waste', $batch->batch_number, $wasteType),
            'created_by' => $actor->id,
        ]);

        InventoryTransactionItem::query()->create([
            'inventory_transaction_id' => $tx->id,
            'material_id' => $materialId,
            'quantity' => $quantity,
            'unit_cost' => 0,
            'total_value' => 0,
            'line_order' => 1,
        ]);

        try {
            $this->inventoryPosting->post($tx, $actor);
        } catch (InventoryPostingException $e) {
            throw new BagBatchException($e->getMessage(), 0, $e);
        }

        $this->link($batch, $tx, $wasteType.'_waste');

        BagBatchWaste::query()->create([
            'bag_production_batch_id' => $batch->id,
            'waste_type' => $wasteType,
            'material_id' => $materialId,
            'warehouse_id' => $warehouseId,
            'quantity_kg' => $quantity,
            'recorded_at' => now(),
            'inventory_transaction_id' => $tx->id,
        ]);
    }

    private function recordProcessLoss(BagProductionBatch $batch): void
    {
        $qty = (float) $batch->process_loss_kg;
        if ($qty <= 0) {
            return;
        }

        BagBatchWaste::query()->create([
            'bag_production_batch_id' => $batch->id,
            'waste_type' => 'process_loss',
            'material_id' => null,
            'warehouse_id' => null,
            'quantity_kg' => $qty,
            'recorded_at' => now(),
            'inventory_transaction_id' => null,
        ]);
    }

    private function link(BagProductionBatch $batch, InventoryTransaction $tx, string $linkType): void
    {
        BagBatchInventoryLink::query()->create([
            'bag_production_batch_id' => $batch->id,
            'inventory_transaction_id' => $tx->id,
            'link_type' => $linkType,
        ]);
    }

    private function effectiveWeightPerBagG(BagProductionBatch $batch): float
    {
        $actual = (float) ($batch->actual_weight_per_good_bag_g ?? 0);
        if ($actual > 0) {
            return $actual;
        }

        return $batch->order?->variant?->effectiveWeightPerBagG() ?? 0;
    }

    private function recordAccountingHook(BagProductionBatch $batch): void
    {
        if (! config('bag.accounting.enabled', false)) {
            return;
        }
    }
}