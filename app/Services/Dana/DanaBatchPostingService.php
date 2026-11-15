<?php

namespace App\Services\Dana;

use App\Models\DanaBatch;
use App\Models\DanaBatchInventoryLink;
use App\Models\DanaBatchWaste;
use App\Models\DanaProductionMachineSetting;
use App\Models\InventoryBalance;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\User;
use App\Services\Inventory\InventoryPostingException;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use Illuminate\Support\Facades\DB;

class DanaBatchPostingService
{
    public function __construct(
        private readonly InventoryPostingService $inventoryPosting,
        private readonly InventoryTransactionNumberGenerator $numbers,
        private readonly DanaBatchReversalService $reversal,
    ) {
    }

    public function post(DanaBatch $batch, User $actor): DanaBatch
    {
        return DB::transaction(function () use ($batch, $actor) {
            $locked = DanaBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->with(['materialInputs', 'operators'])
                ->firstOrFail();

            if ($locked->isPosted()) {
                throw new DanaBatchException('Batch is already posted.');
            }

            if ($locked->status !== 'completed') {
                throw new DanaBatchException('Only completed batches can be posted.');
            }

            if ($locked->inventoryLinks()->exists()) {
                throw new DanaBatchException('Batch already has inventory postings.');
            }

            $this->validateMassBalance($locked);
            $this->validateProcessLoss($locked);
            $this->validateMachineCapacity($locked);

            $this->snapshotInputCosts($locked);
            $costs = $this->computeCosts($locked);

            $occurredDate = $locked->production_date;

            $issueTx = $this->createIssueTransaction($locked, $actor, $costs, $occurredDate);
            $this->inventoryPosting->post($issueTx, $actor);

            DanaBatchInventoryLink::query()->create([
                'dana_batch_id' => $locked->id,
                'inventory_transaction_id' => $issueTx->id,
                'link_type' => 'consumption',
            ]);

            if ((float) $locked->output_kg > 0) {
                $danaTx = $this->createProductionReceipt(
                    $locked,
                    $actor,
                    $occurredDate,
                    (float) $locked->output_kg,
                    $costs['cost_per_kg'],
                    (int) $locked->finished_dana_warehouse_id,
                    (int) $locked->output_material_id,
                    $locked->output_material_variant_id ? (int) $locked->output_material_variant_id : null,
                );
                $this->inventoryPosting->post($danaTx, $actor);
                $this->link($locked, $danaTx, 'dana_output');
            }

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

            $this->recordProcessLoss($locked, $occurredDate);

            $locked->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actor->id,
                'raw_material_cost' => $costs['raw_material_cost'],
                'color_material_cost' => $costs['color_material_cost'],
                'direct_labor_cost' => $costs['direct_labor_cost'],
                'total_batch_cost' => $costs['total_batch_cost'],
                'cost_per_kg' => $costs['cost_per_kg'],
            ]);

            $this->recordAccountingHook($locked);

            return $locked->fresh([
                'machine',
                'materialInputs.material',
                'operators',
                'wastes',
                'inventoryLinks.inventoryTransaction',
            ]);
        });
    }

    public function reverse(DanaBatch $batch, User $actor, ?string $notes = null): DanaBatch
    {
        return $this->reversal->reverse($batch, $actor, $notes);
    }

  /**
     * @return array{raw_material_cost: float, color_material_cost: float, direct_labor_cost: float, total_batch_cost: float, cost_per_kg: float}
     */
    public function computeCosts(DanaBatch $batch): array
    {
        $batch->loadMissing(['materialInputs.material']);

        $raw = 0.0;
        $color = 0.0;

        foreach ($batch->materialInputs as $line) {
            $unitCost = (float) $line->unit_cost_snapshot > 0
                ? (float) $line->unit_cost_snapshot
                : $this->resolveInputUnitCost($batch, $line);
            $value = (float) $line->quantity_kg * $unitCost;

            if ($line->input_type === 'color') {
                $color += $value;
            } else {
                $raw += $value;
            }
        }

        $labor = (float) $batch->operators->sum('labor_cost');
        $electricity = (float) $batch->electricity_cost;
        $overhead = (float) $batch->other_overhead_cost;
        $total = $raw + $color + $labor + $electricity + $overhead;
        $outputKg = max((float) $batch->output_kg, 0.0001);
        $costPerKg = round($total / $outputKg, 4);

        return [
            'raw_material_cost' => round($raw, 4),
            'color_material_cost' => round($color, 4),
            'direct_labor_cost' => round($labor, 4),
            'total_batch_cost' => round($total, 4),
            'cost_per_kg' => $costPerKg,
        ];
    }

    private function validateMassBalance(DanaBatch $batch): void
    {
        $expected = (float) $batch->output_kg
            + (float) $batch->reusable_waste_kg
            + (float) $batch->scrap_waste_kg
            + (float) $batch->process_loss_kg;

        $delta = abs((float) $batch->total_input_kg - $expected);
        $tolerance = (float) config('dana.mass_balance_tolerance_kg', 0.01);

        if ($delta > $tolerance) {
            throw new DanaBatchException(sprintf(
                'Mass balance mismatch: inputs %.4f KG vs outputs+waste %.4f KG.',
                (float) $batch->total_input_kg,
                $expected,
            ));
        }
    }

    private function validateProcessLoss(DanaBatch $batch): void
    {
        $input = (float) $batch->total_input_kg;
        if ($input <= 0) {
            return;
        }

        $lossPct = ((float) $batch->process_loss_kg / $input) * 100;
        $maxPct = $this->maxProcessLossPercent($batch);

        if ($lossPct > $maxPct + 0.0001) {
            throw new DanaBatchException(sprintf(
                'Process loss %.2f%% exceeds allowed maximum %.2f%%.',
                $lossPct,
                $maxPct,
            ));
        }
    }

    private function maxProcessLossPercent(DanaBatch $batch): float
    {
        $machineSetting = DanaProductionMachineSetting::query()
            ->where('machine_id', $batch->machine_id)
            ->first();

        if ($machineSetting?->max_process_loss_percent !== null) {
            return (float) $machineSetting->max_process_loss_percent;
        }

        if ($batch->dana_production_recipe_id) {
            $recipe = $batch->recipe;
            if ($recipe) {
                return (float) $recipe->max_process_loss_percent;
            }
        }

        return (float) config('dana.default_max_process_loss_percent', 5);
    }

    private function validateMachineCapacity(DanaBatch $batch): void
    {
        $output = (float) $batch->output_kg;
        $setting = DanaProductionMachineSetting::query()
            ->where('machine_id', $batch->machine_id)
            ->first();

        if ($setting?->max_output_kg_per_batch !== null && $output > (float) $setting->max_output_kg_per_batch) {
            throw new DanaBatchException('Output exceeds machine batch capacity limit.');
        }

        $batch->loadMissing('machine');
        $machineCap = $batch->machine?->capacity;
        if ($machineCap !== null && $output > (float) $machineCap) {
            throw new DanaBatchException('Output exceeds machine rated capacity.');
        }
    }

    private function snapshotInputCosts(DanaBatch $batch): void
    {
        $batch->loadMissing(['materialInputs.material']);

        foreach ($batch->materialInputs as $line) {
            $line->update([
                'unit_cost_snapshot' => $this->resolveInputUnitCost($batch, $line),
            ]);
        }

        $batch->load('materialInputs');
    }

    private function resolveInputUnitCost(DanaBatch $batch, $line): float
    {
        $balance = InventoryBalance::query()
            ->where('warehouse_id', $batch->raw_material_warehouse_id)
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

  /**
     * @param  array{raw_material_cost: float, color_material_cost: float, direct_labor_cost: float, total_batch_cost: float, cost_per_kg: float}  $costs
     */
    private function createIssueTransaction(DanaBatch $batch, User $actor, array $costs, $occurredDate): InventoryTransaction
    {
        $tx = InventoryTransaction::query()->create([
            'transaction_number' => $this->numbers->next('issue_to_production'),
            'type' => 'issue_to_production',
            'status' => 'draft',
            'warehouse_id' => $batch->raw_material_warehouse_id,
            'transaction_date' => $occurredDate,
            'reference_type' => DanaBatch::class,
            'reference_id' => $batch->id,
            'notes' => 'Dana batch '.$batch->batch_number.' consumption',
            'created_by' => $actor->id,
        ]);

        $order = 1;
        foreach ($batch->materialInputs as $line) {
            $unitCost = (float) $line->unit_cost_snapshot ?: $this->resolveInputUnitCost($batch, $line);
            InventoryTransactionItem::query()->create([
                'inventory_transaction_id' => $tx->id,
                'material_id' => $line->material_id,
                'material_variant_id' => $line->material_variant_id,
                'quantity' => $line->quantity_kg,
                'unit_cost' => $unitCost,
                'total_value' => round((float) $line->quantity_kg * $unitCost, 4),
                'line_order' => $order++,
            ]);
        }

        return $tx->fresh('items');
    }

    private function createProductionReceipt(
        DanaBatch $batch,
        User $actor,
        $occurredDate,
        float $quantity,
        float $unitCost,
        int $warehouseId,
        int $materialId,
        ?int $variantId,
    ): InventoryTransaction {
        $tx = InventoryTransaction::query()->create([
            'transaction_number' => $this->numbers->next('production_receipt'),
            'type' => 'production_receipt',
            'status' => 'draft',
            'warehouse_id' => $warehouseId,
            'transaction_date' => $occurredDate,
            'reference_type' => DanaBatch::class,
            'reference_id' => $batch->id,
            'notes' => 'Dana batch '.$batch->batch_number.' output',
            'created_by' => $actor->id,
        ]);

        InventoryTransactionItem::query()->create([
            'inventory_transaction_id' => $tx->id,
            'material_id' => $materialId,
            'material_variant_id' => $variantId,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_value' => round($quantity * $unitCost, 4),
            'line_order' => 1,
        ]);

        return $tx->fresh('items');
    }

    private function postWasteStock(
        DanaBatch $batch,
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
            throw new DanaBatchException('Waste warehouse is required when recording '.$wasteType.' waste stock.');
        }

        $tx = InventoryTransaction::query()->create([
            'transaction_number' => $this->numbers->next('waste_receipt'),
            'type' => 'waste_receipt',
            'status' => 'draft',
            'warehouse_id' => $warehouseId,
            'transaction_date' => $occurredDate,
            'reference_type' => DanaBatch::class,
            'reference_id' => $batch->id,
            'notes' => sprintf('Dana batch %s — %s waste', $batch->batch_number, $wasteType),
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
            throw new DanaBatchException($e->getMessage(), 0, $e);
        }

        $this->link($batch, $tx, $wasteType.'_waste');

        DanaBatchWaste::query()->create([
            'dana_batch_id' => $batch->id,
            'waste_type' => $wasteType,
            'material_id' => $materialId,
            'warehouse_id' => $warehouseId,
            'quantity' => $quantity,
            'recorded_at' => now(),
            'inventory_transaction_id' => $tx->id,
        ]);
    }

    private function recordProcessLoss(DanaBatch $batch, $occurredDate): void
    {
        $qty = (float) $batch->process_loss_kg;
        if ($qty <= 0) {
            return;
        }

        DanaBatchWaste::query()->create([
            'dana_batch_id' => $batch->id,
            'waste_type' => 'process_loss',
            'material_id' => null,
            'warehouse_id' => null,
            'quantity' => $qty,
            'recorded_at' => now(),
            'inventory_transaction_id' => null,
        ]);
    }

    private function link(DanaBatch $batch, InventoryTransaction $tx, string $linkType): void
    {
        DanaBatchInventoryLink::query()->create([
            'dana_batch_id' => $batch->id,
            'inventory_transaction_id' => $tx->id,
            'link_type' => $linkType,
        ]);
    }

    private function recordAccountingHook(DanaBatch $batch): void
    {
        if (! config('dana.accounting.enabled', false)) {
            return;
        }

        // Accounting journals (Phase 4) attach here when configured.
    }
}