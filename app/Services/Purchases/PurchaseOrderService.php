<?php

namespace App\Services\Purchases;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        private readonly PurchaseDocumentNumberGenerator $numbers,
        private readonly PurchaseLineCalculator $calculator,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function createDraft(array $header, array $lines, User $actor): PurchaseOrder
    {
        return DB::transaction(function () use ($header, $lines, $actor) {
            $po = PurchaseOrder::query()->create([
                'order_number' => $this->numbers->next('purchase_order'),
                'supplier_id' => $header['supplier_id'],
                'warehouse_id' => $header['warehouse_id'],
                'order_date' => $header['order_date'],
                'expected_date' => $header['expected_date'] ?? null,
                'status' => 'draft',
                'notes' => $header['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->syncLines($po, $lines);
            $this->refreshTotals($po);

            return $po->fresh(['lines.material', 'supplier', 'warehouse']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function updateDraft(PurchaseOrder $order, array $header, array $lines): PurchaseOrder
    {
        if (! $order->isDraft()) {
            throw new PurchaseException('Only draft purchase orders can be edited.');
        }

        return DB::transaction(function () use ($order, $header, $lines) {
            $order->update([
                'supplier_id' => $header['supplier_id'],
                'warehouse_id' => $header['warehouse_id'],
                'order_date' => $header['order_date'],
                'expected_date' => $header['expected_date'] ?? null,
                'notes' => $header['notes'] ?? null,
            ]);

            $order->lines()->delete();
            $this->syncLines($order, $lines);
            $this->refreshTotals($order->fresh());

            return $order->fresh(['lines.material', 'supplier', 'warehouse']);
        });
    }

    public function confirm(PurchaseOrder $order, User $actor): PurchaseOrder
    {
        if (! $order->isDraft()) {
            throw new PurchaseException('Purchase order is not in draft status.');
        }

        if ($order->lines()->count() === 0) {
            throw new PurchaseException('Purchase order has no lines.');
        }

        $order->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => $actor->id,
        ]);

        return $order->fresh();
    }

    public function refreshReceiptStatus(PurchaseOrder $order): void
    {
        $order->load('lines');
        $allReceived = true;
        $anyReceived = false;

        foreach ($order->lines as $line) {
            if ((float) $line->received_qty > 0) {
                $anyReceived = true;
            }
            if ((float) $line->received_qty < (float) $line->ordered_qty) {
                $allReceived = false;
            }
        }

        if ($allReceived && $anyReceived) {
            $order->update(['status' => 'received']);
        } elseif ($anyReceived) {
            $order->update(['status' => 'partially_received']);
        }
    }

  /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncLines(PurchaseOrder $order, array $lines): void
    {
        $sort = 0;
        foreach ($lines as $row) {
            $qty = (float) $row['ordered_qty'];
            $unitPrice = (float) $row['unit_price'];
            $discount = (float) ($row['discount_amount'] ?? 0);
            $taxRate = (float) ($row['tax_rate'] ?? 0);
            $calc = $this->calculator->compute($qty, $unitPrice, $discount, $taxRate);

            PurchaseOrderLine::query()->create([
                'purchase_order_id' => $order->id,
                'material_id' => $row['material_id'],
                'material_variant_id' => $row['material_variant_id'] ?? null,
                'unit_of_measurement_id' => $row['unit_of_measurement_id'],
                'ordered_qty' => $qty,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'tax_rate' => $taxRate,
                'tax_amount' => $calc['tax_amount'],
                'line_total' => $calc['line_total'],
                'sort_order' => $sort++,
            ]);
        }
    }

    private function refreshTotals(PurchaseOrder $order): void
    {
        $lines = $order->lines()->get();
        $totals = $this->calculator->summarize($lines);

        $order->update([
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'tax_total' => $totals['tax_total'],
            'grand_total' => $totals['grand_total'],
        ]);
    }
}