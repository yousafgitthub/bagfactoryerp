<?php

namespace App\Services\Sales;

use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use App\Services\Purchases\PurchaseLineCalculator;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    public function __construct(
        private readonly SalesDocumentNumberGenerator $numbers,
        private readonly PurchaseLineCalculator $calculator,
        private readonly SalesLineMaterialResolver $resolver,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function createDraft(array $header, array $lines, User $actor): SalesOrder
    {
        return DB::transaction(function () use ($header, $lines, $actor) {
            $order = SalesOrder::query()->create([
                'order_number' => $this->numbers->next('sales_order'),
                'customer_id' => $header['customer_id'],
                'warehouse_id' => $header['warehouse_id'],
                'order_date' => $header['order_date'],
                'expected_date' => $header['expected_date'] ?? null,
                'status' => 'draft',
                'notes' => $header['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->syncLines($order, $lines);
            $this->refreshTotals($order);

            return $order->fresh(['lines.material', 'customer', 'warehouse']);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function updateDraft(SalesOrder $order, array $header, array $lines): SalesOrder
    {
        if (! $order->isDraft()) {
            throw new SalesException('Only draft sales orders can be edited.');
        }

        return DB::transaction(function () use ($order, $header, $lines) {
            $order->update([
                'customer_id' => $header['customer_id'],
                'warehouse_id' => $header['warehouse_id'],
                'order_date' => $header['order_date'],
                'expected_date' => $header['expected_date'] ?? null,
                'notes' => $header['notes'] ?? null,
            ]);

            $order->lines()->delete();
            $this->syncLines($order, $lines);
            $this->refreshTotals($order->fresh());

            return $order->fresh(['lines.material', 'customer', 'warehouse']);
        });
    }

    public function confirm(SalesOrder $order, User $actor): SalesOrder
    {
        if (! $order->isDraft()) {
            throw new SalesException('Sales order is not in draft status.');
        }

        if ($order->lines()->count() === 0) {
            throw new SalesException('Sales order has no lines.');
        }

        $order->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
            'confirmed_by' => $actor->id,
        ]);

        return $order->fresh();
    }

    public function refreshInvoiceStatus(SalesOrder $order): void
    {
        $order->load('lines');
        $allInvoiced = true;
        $anyInvoiced = false;

        foreach ($order->lines as $line) {
            if ((float) $line->invoiced_qty > 0) {
                $anyInvoiced = true;
            }
            if ((float) $line->invoiced_qty < (float) $line->ordered_qty) {
                $allInvoiced = false;
            }
        }

        $status = match (true) {
            $allInvoiced && $anyInvoiced => 'invoiced',
            $anyInvoiced => 'partially_invoiced',
            default => $order->status,
        };

        if ($status !== $order->status && $order->isConfirmed()) {
            $order->update(['status' => $status]);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncLines(SalesOrder $order, array $lines): void
    {
        $sort = 0;
        foreach ($lines as $row) {
            $qty = (float) $row['ordered_qty'];
            if ($qty <= 0) {
                continue;
            }

            $resolved = $this->resolver->resolve($row);
            $unitPrice = (float) $row['unit_price'];
            $discount = (float) ($row['discount_amount'] ?? 0);
            $taxRate = (float) ($row['tax_rate'] ?? 0);
            $calc = $this->calculator->compute($qty, $unitPrice, $discount, $taxRate);

            SalesOrderLine::query()->create([
                'sales_order_id' => $order->id,
                'bag_product_id' => $resolved['bag_product_id'],
                'bag_product_variant_id' => $resolved['bag_product_variant_id'],
                'material_id' => $resolved['material_id'],
                'material_variant_id' => $resolved['material_variant_id'],
                'unit_of_measurement_id' => $resolved['unit_of_measurement_id'],
                'size_label' => $resolved['size_label'],
                'color_label' => $resolved['color_label'],
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

    private function refreshTotals(SalesOrder $order): void
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