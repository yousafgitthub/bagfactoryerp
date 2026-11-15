<?php

namespace App\Services\Purchases;

use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnLine;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    public function __construct(
        private readonly PurchaseDocumentNumberGenerator $numbers,
        private readonly PurchaseLineCalculator $calculator,
        private readonly InventoryTransactionNumberGenerator $inventoryNumbers,
        private readonly InventoryPostingService $inventoryPosting,
        private readonly SupplierLedgerService $ledger,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function createDraft(Supplier $supplier, array $header, array $lines, User $actor): PurchaseReturn
    {
        return DB::transaction(function () use ($supplier, $header, $lines, $actor) {
            $document = PurchaseReturn::query()->create([
                'return_number' => $this->numbers->next('purchase_return'),
                'supplier_id' => $supplier->id,
                'purchase_order_id' => $header['purchase_order_id'] ?? null,
                'goods_receipt_id' => $header['goods_receipt_id'] ?? null,
                'warehouse_id' => $header['warehouse_id'],
                'return_date' => $header['return_date'],
                'status' => 'draft',
                'notes' => $header['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $subtotal = 0.0;
            foreach ($lines as $row) {
                $qty = (float) $row['quantity'];
                $unitPrice = (float) $row['unit_price'];
                $unitCost = (float) ($row['unit_cost'] ?? $unitPrice);
                $lineTotal = $this->calculator->compute($qty, $unitPrice, 0, 0)['line_total'];
                $subtotal += $lineTotal;

                PurchaseReturnLine::query()->create([
                    'purchase_return_id' => $document->id,
                    'purchase_order_line_id' => $row['purchase_order_line_id'] ?? null,
                    'material_id' => $row['material_id'],
                    'material_variant_id' => $row['material_variant_id'] ?? null,
                    'quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                ]);
            }

            $document->update(['subtotal' => round($subtotal, 2)]);

            return $document->fresh(['lines.material']);
        });
    }

    public function post(PurchaseReturn $purchaseReturn, User $actor): PurchaseReturn
    {
        if (! $purchaseReturn->isDraft()) {
            throw new PurchaseException('Only draft purchase returns can be posted.');
        }

        return DB::transaction(function () use ($purchaseReturn, $actor) {
            $locked = PurchaseReturn::query()->whereKey($purchaseReturn->id)->lockForUpdate()->with('lines', 'supplier')->firstOrFail();

            if ($locked->lines->isEmpty()) {
                throw new PurchaseException('Return has no lines.');
            }

            if ($locked->inventory_transaction_id) {
                throw new PurchaseException('Return already posted to inventory.');
            }

            $inventory = InventoryTransaction::query()->create([
                'transaction_number' => $this->inventoryNumbers->next('purchase_return'),
                'type' => 'purchase_return',
                'status' => 'draft',
                'warehouse_id' => $locked->warehouse_id,
                'transaction_date' => $locked->return_date,
                'reference_type' => PurchaseReturn::class,
                'reference_id' => $locked->id,
                'notes' => "Purchase return {$locked->return_number}",
                'created_by' => $actor->id,
            ]);

            foreach ($locked->lines as $line) {
                InventoryTransactionItem::query()->create([
                    'inventory_transaction_id' => $inventory->id,
                    'material_id' => $line->material_id,
                    'material_variant_id' => $line->material_variant_id,
                    'quantity' => $line->quantity,
                    'unit_cost' => $line->unit_cost,
                ]);

                if ($line->purchase_order_line_id) {
                    PurchaseOrderLine::query()->whereKey($line->purchase_order_line_id)->increment(
                        'returned_qty',
                        (float) $line->quantity,
                    );
                }
            }

            $this->inventoryPosting->post($inventory, $actor);

            $locked->update([
                'status' => 'posted',
                'inventory_transaction_id' => $inventory->id,
                'posted_at' => now(),
                'posted_by' => $actor->id,
            ]);

            $this->ledger->recordReturn(
                supplier: $locked->supplier,
                entryDate: $locked->return_date->toDateString(),
                returnId: $locked->id,
                returnNumber: $locked->return_number,
                amount: (float) $locked->subtotal,
                actor: $actor,
            );

            return $locked->fresh(['lines', 'inventoryTransaction']);
        });
    }
}