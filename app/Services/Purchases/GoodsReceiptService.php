<?php

namespace App\Services\Purchases;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use Illuminate\Support\Facades\DB;

class GoodsReceiptService
{
    public function __construct(
        private readonly PurchaseDocumentNumberGenerator $numbers,
        private readonly PurchaseLineCalculator $calculator,
        private readonly InventoryTransactionNumberGenerator $inventoryNumbers,
        private readonly InventoryPostingService $inventoryPosting,
        private readonly PurchaseOrderService $purchaseOrders,
        private readonly AccountingIntegrationService $accounting,
    ) {
    }

    /**
     * @param  list<array{purchase_order_line_id: int, received_qty: float}>  $lines
     */
    public function createDraft(PurchaseOrder $order, string $receiptDate, array $lines, User $actor, ?string $notes = null): GoodsReceipt
    {
        if (! $order->isConfirmed()) {
            throw new PurchaseException('Purchase order must be confirmed before receiving goods.');
        }

        return DB::transaction(function () use ($order, $receiptDate, $lines, $actor, $notes) {
            $grn = GoodsReceipt::query()->create([
                'receipt_number' => $this->numbers->next('goods_receipt'),
                'purchase_order_id' => $order->id,
                'supplier_id' => $order->supplier_id,
                'warehouse_id' => $order->warehouse_id,
                'receipt_date' => $receiptDate,
                'status' => 'draft',
                'notes' => $notes,
                'created_by' => $actor->id,
            ]);

            $this->syncLines($grn, $order, $lines);

            return $grn->fresh(['lines.material', 'purchaseOrder']);
        });
    }

    public function post(GoodsReceipt $receipt, User $actor): GoodsReceipt
    {
        if (! $receipt->isDraft()) {
            throw new PurchaseException('Only draft goods receipts can be posted.');
        }

        if ($receipt->inventory_transaction_id) {
            throw new PurchaseException('Goods receipt already linked to inventory.');
        }

        return DB::transaction(function () use ($receipt, $actor) {
            $locked = GoodsReceipt::query()->whereKey($receipt->id)->lockForUpdate()->with('lines', 'purchaseOrder.lines')->firstOrFail();

            if ($locked->lines->isEmpty()) {
                throw new PurchaseException('Goods receipt has no lines.');
            }

            foreach ($locked->lines as $line) {
                $poLine = $locked->purchaseOrder->lines->firstWhere('id', $line->purchase_order_line_id);
                if (! $poLine) {
                    throw new PurchaseException('Invalid purchase order line on receipt.');
                }

                $remaining = $poLine->remainingReceivableQty();
                if ((float) $line->received_qty > $remaining + 0.0001) {
                    throw new PurchaseException("Received quantity exceeds remaining on PO line #{$poLine->id}.");
                }
            }

            $inventory = InventoryTransaction::query()->create([
                'transaction_number' => $this->inventoryNumbers->next('purchase_receipt'),
                'type' => 'purchase_receipt',
                'status' => 'draft',
                'warehouse_id' => $locked->warehouse_id,
                'transaction_date' => $locked->receipt_date,
                'reference_type' => GoodsReceipt::class,
                'reference_id' => $locked->id,
                'notes' => "GRN {$locked->receipt_number}",
                'created_by' => $actor->id,
            ]);

            foreach ($locked->lines as $line) {
                InventoryTransactionItem::query()->create([
                    'inventory_transaction_id' => $inventory->id,
                    'material_id' => $line->material_id,
                    'material_variant_id' => $line->material_variant_id,
                    'quantity' => $line->received_qty,
                    'unit_cost' => $line->unit_cost,
                ]);

                PurchaseOrderLine::query()->whereKey($line->purchase_order_line_id)->increment(
                    'received_qty',
                    (float) $line->received_qty,
                );
            }

            $this->inventoryPosting->post($inventory, $actor);

            $locked->update([
                'status' => 'posted',
                'inventory_transaction_id' => $inventory->id,
                'posted_at' => now(),
                'posted_by' => $actor->id,
            ]);

            $this->purchaseOrders->refreshReceiptStatus($locked->purchaseOrder->fresh('lines'));

            $this->accounting->postGoodsReceipt($locked->fresh(['inventoryTransaction']), $actor);

            return $locked->fresh(['lines', 'inventoryTransaction']);
        });
    }

    /**
     * @param  list<array{purchase_order_line_id: int, received_qty: float}>  $lines
     */
    private function syncLines(GoodsReceipt $grn, PurchaseOrder $order, array $lines): void
    {
        $order->load('lines');

        foreach ($lines as $row) {
            $qty = (float) $row['received_qty'];
            if ($qty <= 0) {
                continue;
            }

            /** @var PurchaseOrderLine|null $poLine */
            $poLine = $order->lines->firstWhere('id', (int) $row['purchase_order_line_id']);
            if (! $poLine) {
                throw new PurchaseException('Invalid PO line for goods receipt.');
            }

            if ($qty > $poLine->remainingReceivableQty() + 0.0001) {
                throw new PurchaseException('Receive quantity exceeds open PO quantity.');
            }

            $unitPrice = (float) $poLine->unit_price;
            $lineTotal = $this->calculator->compute($qty, $unitPrice, 0, 0)['line_total'];

            GoodsReceiptLine::query()->create([
                'goods_receipt_id' => $grn->id,
                'purchase_order_line_id' => $poLine->id,
                'material_id' => $poLine->material_id,
                'material_variant_id' => $poLine->material_variant_id,
                'received_qty' => $qty,
                'unit_price' => $unitPrice,
                'unit_cost' => $unitPrice,
                'line_total' => $lineTotal,
            ]);
        }
    }
}