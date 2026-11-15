<?php

namespace App\Services\Inventory;

use App\Models\InventoryBalance;
use App\Models\InventoryLedgerEntry;
use App\Models\InventoryTransaction;
use App\Models\Material;
use App\Models\StockLot;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InventoryPostingService
{
    public function __construct(
        private readonly InventoryReconciliationService $reconciliation,
    ) {
    }

    public function post(InventoryTransaction $transaction, User $actor): InventoryTransaction
    {
        return DB::transaction(function () use ($transaction, $actor) {
            $locked = InventoryTransaction::query()
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->with(['items.material', 'stockAdjustment', 'stockTransfer.items'])
                ->firstOrFail();

            if (! $locked->isDraft()) {
                throw new InventoryPostingException('Only draft transactions can be posted.');
            }

            if ($locked->ledgerEntries()->exists()) {
                throw new InventoryPostingException('Transaction already has ledger entries.');
            }

            if ($locked->items->isEmpty() && $locked->type !== 'reversal') {
                throw new InventoryPostingException('Transaction has no line items.');
            }

            $occurredAt = $locked->transaction_date->startOfDay();

            match ($locked->type) {
                'warehouse_transfer' => $this->postTransfer($locked, $actor, $occurredAt),
                'adjustment' => $this->postAdjustment($locked, $actor, $occurredAt),
                'reversal' => $this->postReversal($locked, $actor, $occurredAt),
                default => $this->postSimple($locked, $actor, $occurredAt),
            };

            $locked->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actor->id,
            ]);

            return $locked->fresh(['items', 'ledgerEntries']);
        });
    }

    private function postSimple(InventoryTransaction $transaction, User $actor, $occurredAt): void
    {
        foreach ($transaction->items as $item) {
            $signedQty = $this->signedQuantityForType($transaction->type, (float) $item->quantity);
            $unitCost = (float) $item->unit_cost;

            if ($signedQty > 0 && $unitCost <= 0) {
                $unitCost = (float) ($item->material?->default_unit_cost ?? 0);
            }

            if ($signedQty < 0) {
                $balance = $this->lockBalance(
                    (int) $transaction->warehouse_id,
                    (int) $item->material_id,
                    $item->material_variant_id,
                );
                $unitCost = (float) $balance->average_unit_cost;
            }

            $this->applyMovement(
                transaction: $transaction,
                itemId: $item->id,
                warehouseId: (int) $transaction->warehouse_id,
                materialId: (int) $item->material_id,
                variantId: $item->material_variant_id ? (int) $item->material_variant_id : null,
                signedQty: $signedQty,
                unitCost: $unitCost,
                movementType: $this->movementTypeFor($transaction->type, $signedQty),
                occurredAt: $occurredAt,
                actor: $actor,
                reference: $transaction->transaction_number,
                recordLot: in_array($transaction->type, ['purchase_receipt', 'opening_stock', 'waste_receipt', 'return_unused', 'production_receipt'], true),
            );
        }
    }

    private function postAdjustment(InventoryTransaction $transaction, User $actor, $occurredAt): void
    {
        $direction = $transaction->stockAdjustment?->direction ?? 'increase';

        foreach ($transaction->items as $item) {
            $qty = (float) $item->quantity;
            $signedQty = $direction === 'decrease' ? -$qty : $qty;
            $unitCost = (float) $item->unit_cost;

            if ($signedQty < 0) {
                $balance = $this->lockBalance(
                    (int) $transaction->warehouse_id,
                    (int) $item->material_id,
                    $item->material_variant_id,
                );
                $unitCost = (float) $balance->average_unit_cost;
            } elseif ($unitCost <= 0) {
                $unitCost = (float) ($item->material?->default_unit_cost ?? 0);
            }

            $this->applyMovement(
                transaction: $transaction,
                itemId: $item->id,
                warehouseId: (int) $transaction->warehouse_id,
                materialId: (int) $item->material_id,
                variantId: $item->material_variant_id ? (int) $item->material_variant_id : null,
                signedQty: $signedQty,
                unitCost: $unitCost,
                movementType: 'adjustment',
                occurredAt: $occurredAt,
                actor: $actor,
                reference: $transaction->transaction_number,
                recordLot: $signedQty > 0,
            );
        }
    }

    private function postTransfer(InventoryTransaction $transaction, User $actor, $occurredAt): void
    {
        $transfer = $transaction->stockTransfer;
        if (! $transfer) {
            throw new InventoryPostingException('Warehouse transfer metadata is missing.');
        }

        $fromId = (int) $transfer->from_warehouse_id;
        $toId = (int) $transfer->to_warehouse_id;

        foreach ($transfer->items as $line) {
            $qty = (float) $line->quantity;
            $variantId = $line->material_variant_id ? (int) $line->material_variant_id : null;

            $sourceBalance = $this->lockBalance($fromId, (int) $line->material_id, $line->material_variant_id);
            $unitCost = (float) $sourceBalance->average_unit_cost;

            $this->applyMovement(
                transaction: $transaction,
                itemId: null,
                warehouseId: $fromId,
                materialId: (int) $line->material_id,
                variantId: $variantId,
                signedQty: -$qty,
                unitCost: $unitCost,
                movementType: 'transfer_out',
                occurredAt: $occurredAt,
                actor: $actor,
                reference: $transaction->transaction_number,
                recordLot: false,
            );

            $this->applyMovement(
                transaction: $transaction,
                itemId: null,
                warehouseId: $toId,
                materialId: (int) $line->material_id,
                variantId: $variantId,
                signedQty: $qty,
                unitCost: $unitCost,
                movementType: 'transfer_in',
                occurredAt: $occurredAt,
                actor: $actor,
                reference: $transaction->transaction_number,
                recordLot: false,
            );
        }
    }

    private function postReversal(InventoryTransaction $transaction, User $actor, $occurredAt): void
    {
        if (! $transaction->reversal_of_id) {
            throw new InventoryPostingException('Reversal requires reversal_of_id.');
        }

        $original = InventoryTransaction::query()
            ->with('ledgerEntries')
            ->findOrFail($transaction->reversal_of_id);

        foreach ($original->ledgerEntries as $entry) {
            $this->applyMovement(
                transaction: $transaction,
                itemId: null,
                warehouseId: (int) $entry->warehouse_id,
                materialId: (int) $entry->material_id,
                variantId: $entry->material_variant_id ? (int) $entry->material_variant_id : null,
                signedQty: -(float) $entry->quantity,
                unitCost: (float) $entry->unit_cost,
                movementType: 'reversal',
                occurredAt: $occurredAt,
                actor: $actor,
                reference: $transaction->transaction_number,
                recordLot: false,
            );
        }
    }

    private function applyMovement(
        InventoryTransaction $transaction,
        ?int $itemId,
        int $warehouseId,
        int $materialId,
        ?int $variantId,
        float $signedQty,
        float $unitCost,
        string $movementType,
        $occurredAt,
        User $actor,
        string $reference,
        bool $recordLot,
    ): void {
        if ($signedQty === 0.0) {
            return;
        }

        $balance = $this->lockBalance($warehouseId, $materialId, $variantId);

        $newQty = (float) $balance->quantity + $signedQty;

        if ($newQty < 0 && ! config('inventory.allow_negative_stock')) {
            throw new InventoryPostingException(sprintf(
                'Insufficient stock for material #%d in warehouse #%d.',
                $materialId,
                $warehouseId,
            ));
        }

        if ($signedQty > 0) {
            $inboundValue = $signedQty * $unitCost;
            $totalValue = (float) $balance->total_value + $inboundValue;
            $average = $newQty > 0 ? $totalValue / $newQty : 0.0;
        } else {
            $average = (float) $balance->average_unit_cost;
            $totalValue = $newQty * $average;
        }

        $balance->update([
            'quantity' => $newQty,
            'average_unit_cost' => round($average, 4),
            'total_value' => round($totalValue, 4),
        ]);

        $lotId = null;
        if ($recordLot && $signedQty > 0) {
            $lot = StockLot::query()->create([
                'material_id' => $materialId,
                'material_variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'lot_number' => $reference.'-'.$materialId,
                'received_at' => $occurredAt,
                'quantity_received' => $signedQty,
                'quantity_remaining' => $signedQty,
                'unit_cost' => $unitCost,
                'source_transaction_id' => $transaction->id,
            ]);
            $lotId = $lot->id;
        }

        InventoryLedgerEntry::query()->create([
            'inventory_transaction_id' => $transaction->id,
            'inventory_transaction_item_id' => $itemId,
            'movement_type' => $movementType,
            'warehouse_id' => $warehouseId,
            'material_id' => $materialId,
            'material_variant_id' => $variantId,
            'quantity' => $signedQty,
            'unit_cost' => $unitCost,
            'total_value' => round($signedQty * $unitCost, 4),
            'occurred_at' => $occurredAt,
            'reference' => $reference,
            'stock_lot_id' => $lotId,
            'created_by' => $actor->id,
        ]);
    }

    private function lockBalance(int $warehouseId, int $materialId, ?int $variantId): InventoryBalance
    {
        $query = InventoryBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('material_id', $materialId);

        if ($variantId === null) {
            $query->whereNull('material_variant_id');
        } else {
            $query->where('material_variant_id', $variantId);
        }

        $balance = $query->lockForUpdate()->first();

        if ($balance) {
            return $balance;
        }

        $material = Material::query()->findOrFail($materialId);

        return InventoryBalance::query()->create([
            'warehouse_id' => $warehouseId,
            'material_id' => $materialId,
            'material_variant_id' => $variantId,
            'bucket' => $material->inventory_bucket,
            'quantity' => 0,
            'average_unit_cost' => 0,
            'total_value' => 0,
        ]);
    }

    private function signedQuantityForType(string $type, float $quantity): float
    {
        return match ($type) {
            'issue_to_production', 'purchase_return' => -abs($quantity),
            'opening_stock', 'purchase_receipt', 'return_unused', 'waste_receipt', 'production_receipt' => abs($quantity),
            default => $quantity,
        };
    }

    private function movementTypeFor(string $type, float $signedQty): string
    {
        return match ($type) {
            'issue_to_production', 'purchase_return' => 'issue',
            'opening_stock' => 'opening',
            'purchase_receipt' => 'receipt',
            'return_unused' => 'return',
            'waste_receipt' => 'waste',
            'production_receipt' => 'production',
            default => $signedQty >= 0 ? 'receipt' : 'issue',
        };
    }

    /**
     * @return list<array{warehouse_id: int, material_id: int, material_variant_id: int|null, balance_qty: float, ledger_qty: float, delta: float}>
     */
    public function reconcile(): array
    {
        return $this->reconciliation->compareBalancesToLedger();
    }
}