<?php

namespace App\Services\Sales;

use App\Models\CustomerLedgerEntry;
use App\Models\InventoryBalance;
use App\Models\InventoryLedgerEntry;
use App\Models\InventoryTransaction;
use App\Models\InventoryTransactionItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\User;
use App\Services\Accounting\AccountingIntegrationService;
use App\Services\Inventory\InventoryPostingService;
use App\Services\Inventory\InventoryTransactionNumberGenerator;
use App\Services\Purchases\PurchaseLineCalculator;
use Illuminate\Support\Facades\DB;

class SalesInvoiceService
{
    public function __construct(
        private readonly SalesDocumentNumberGenerator $numbers,
        private readonly PurchaseLineCalculator $calculator,
        private readonly SalesLineMaterialResolver $resolver,
        private readonly InventoryTransactionNumberGenerator $inventoryNumbers,
        private readonly InventoryPostingService $inventoryPosting,
        private readonly CustomerLedgerService $ledger,
        private readonly CustomerReceiptService $receipts,
        private readonly SalesOrderService $orders,
        private readonly AccountingIntegrationService $accounting,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function createDraft(array $header, array $lines, User $actor): SalesInvoice
    {
        return DB::transaction(function () use ($header, $lines, $actor) {
            $invoice = SalesInvoice::query()->create([
                'invoice_number' => $this->numbers->next('sales_invoice'),
                'customer_id' => $header['customer_id'],
                'sales_order_id' => $header['sales_order_id'] ?? null,
                'warehouse_id' => $header['warehouse_id'],
                'invoice_date' => $header['invoice_date'],
                'due_date' => $header['due_date'] ?? null,
                'sale_type' => $header['sale_type'] ?? 'credit',
                'status' => 'draft',
                'notes' => $header['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->syncLines($invoice, $lines);
            $this->refreshTotals($invoice);

            return $invoice->fresh(['lines.material', 'customer']);
        });
    }

    /**
     * @param  list<array{sales_order_line_id: int, quantity: float}>  $lines
     */
    public function createFromSalesOrder(SalesOrder $order, array $header, array $lines, User $actor): SalesInvoice
    {
        if (! $order->isConfirmed()) {
            throw new SalesException('Sales order must be confirmed before invoicing.');
        }

        $order->load('lines');

        $header['customer_id'] = $order->customer_id;
        $header['sales_order_id'] = $order->id;
        $header['warehouse_id'] = $order->warehouse_id;

        $normalized = [];
        foreach ($lines as $row) {
            $soLine = $order->lines->firstWhere('id', (int) $row['sales_order_line_id']);
            if (! $soLine) {
                throw new SalesException('Invalid sales order line on invoice.');
            }

            $qty = (float) $row['quantity'];
            if ($qty > $soLine->remainingInvoiceableQty() + 0.0001) {
                throw new SalesException('Invoice quantity exceeds open sales order quantity.');
            }

            $normalized[] = [
                'sales_order_line_id' => $soLine->id,
                'bag_product_id' => $soLine->bag_product_id,
                'bag_product_variant_id' => $soLine->bag_product_variant_id,
                'material_id' => $soLine->material_id,
                'material_variant_id' => $soLine->material_variant_id,
                'unit_of_measurement_id' => $soLine->unit_of_measurement_id,
                'size_label' => $soLine->size_label,
                'color_label' => $soLine->color_label,
                'quantity' => $qty,
                'unit_price' => (float) $soLine->unit_price,
                'discount_amount' => (float) $soLine->discount_amount,
                'tax_rate' => (float) $soLine->tax_rate,
            ];
        }

        return $this->createDraft($header, $normalized, $actor);
    }

    public function post(SalesInvoice $invoice, User $actor, ?array $cashReceipt = null): SalesInvoice
    {
        if (! $invoice->isDraft()) {
            throw new SalesException('Only draft invoices can be posted.');
        }

        return DB::transaction(function () use ($invoice, $actor, $cashReceipt) {
            $locked = SalesInvoice::query()->whereKey($invoice->id)->lockForUpdate()->with('lines', 'customer')->firstOrFail();

            if ($locked->lines->isEmpty()) {
                throw new SalesException('Invoice has no lines.');
            }

            if ($locked->inventory_transaction_id) {
                throw new SalesException('Invoice already posted to inventory.');
            }

            if (CustomerLedgerEntry::query()
                ->where('reference_type', 'sales_invoice')
                ->where('reference_id', $locked->id)
                ->exists()) {
                throw new SalesException('Invoice already posted to customer ledger.');
            }

            foreach ($locked->lines as $line) {
                $this->assertStockAvailable($locked, $line, $actor);

                if ($line->sales_order_line_id) {
                    $soLine = SalesOrderLine::query()->lockForUpdate()->findOrFail($line->sales_order_line_id);
                    if ((float) $line->quantity > $soLine->remainingInvoiceableQty() + 0.0001) {
                        throw new SalesException('Invoice quantity exceeds open sales order line.');
                    }
                }
            }

            $inventory = InventoryTransaction::query()->create([
                'transaction_number' => $this->inventoryNumbers->next('sales_issue'),
                'type' => 'sales_issue',
                'status' => 'draft',
                'warehouse_id' => $locked->warehouse_id,
                'transaction_date' => $locked->invoice_date,
                'reference_type' => SalesInvoice::class,
                'reference_id' => $locked->id,
                'notes' => "Sales invoice {$locked->invoice_number}",
                'created_by' => $actor->id,
            ]);

            foreach ($locked->lines as $line) {
                InventoryTransactionItem::query()->create([
                    'inventory_transaction_id' => $inventory->id,
                    'material_id' => $line->material_id,
                    'material_variant_id' => $line->material_variant_id,
                    'quantity' => $line->quantity,
                    'unit_cost' => 0,
                ]);
            }

            $this->inventoryPosting->post($inventory, $actor);

            $cogs = (float) InventoryLedgerEntry::query()
                ->where('inventory_transaction_id', $inventory->id)
                ->where('quantity', '<', 0)
                ->sum(DB::raw('ABS(total_value)'));

            foreach ($locked->lines as $line) {
                if ($line->sales_order_line_id) {
                    SalesOrderLine::query()->whereKey($line->sales_order_line_id)->increment('invoiced_qty', (float) $line->quantity);
                }
            }

            if ($locked->sales_order_id) {
                $this->orders->refreshInvoiceStatus($locked->salesOrder()->first());
            }

            $locked->update([
                'status' => 'posted',
                'cogs_total' => round($cogs, 2),
                'inventory_transaction_id' => $inventory->id,
                'posted_at' => now(),
                'posted_by' => $actor->id,
            ]);

            $this->ledger->recordInvoice(
                customer: $locked->customer,
                entryDate: $locked->invoice_date->toDateString(),
                invoiceId: $locked->id,
                invoiceNumber: $locked->invoice_number,
                amount: (float) $locked->grand_total,
                actor: $actor,
            );

            $this->accounting->postSalesInvoice($locked->fresh(), $actor);

            $autoCash = $locked->sale_type === 'cash' || ($cashReceipt['record_immediately'] ?? false);
            if ($autoCash) {
                $this->receipts->record($locked->customer, [
                    'receipt_date' => $cashReceipt['receipt_date'] ?? $locked->invoice_date->toDateString(),
                    'amount' => (float) $locked->grand_total,
                    'payment_method' => $cashReceipt['payment_method'] ?? 'cash',
                    'reference' => $cashReceipt['reference'] ?? null,
                    'notes' => 'Payment on invoice post',
                ], [
                    ['sales_invoice_id' => $locked->id, 'amount' => (float) $locked->grand_total],
                ], $actor);
            }

            return $locked->fresh(['lines', 'inventoryTransaction']);
        });
    }

    private function assertStockAvailable(SalesInvoice $invoice, SalesInvoiceLine $line, User $actor): void
    {
        $query = InventoryBalance::query()
            ->where('warehouse_id', $invoice->warehouse_id)
            ->where('material_id', $line->material_id);

        if ($line->material_variant_id) {
            $query->where('material_variant_id', $line->material_variant_id);
        } else {
            $query->whereNull('material_variant_id');
        }

        $balance = $query->first();
        $available = (float) ($balance?->quantity ?? 0);
        $required = (float) $line->quantity;

        if ($required > $available + 0.0001) {
            $canOversell = config('sales.allow_oversell') && $actor->can('sales.oversell');

            if (! $canOversell) {
                throw new SalesException("Insufficient finished stock for material #{$line->material_id} (need {$required}, have {$available}).");
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncLines(SalesInvoice $invoice, array $lines): void
    {
        $sort = 0;
        foreach ($lines as $row) {
            $qty = (float) $row['quantity'];
            $resolved = isset($row['material_id']) && $row['material_id']
                ? [
                    'bag_product_id' => $row['bag_product_id'] ?? null,
                    'bag_product_variant_id' => $row['bag_product_variant_id'] ?? null,
                    'material_id' => (int) $row['material_id'],
                    'material_variant_id' => $row['material_variant_id'] ?? null,
                    'unit_of_measurement_id' => (int) $row['unit_of_measurement_id'],
                    'size_label' => $row['size_label'] ?? null,
                    'color_label' => $row['color_label'] ?? null,
                ]
                : $this->resolver->resolve($row);

            $unitPrice = (float) $row['unit_price'];
            $discount = (float) ($row['discount_amount'] ?? 0);
            $taxRate = (float) ($row['tax_rate'] ?? 0);
            $calc = $this->calculator->compute($qty, $unitPrice, $discount, $taxRate);

            SalesInvoiceLine::query()->create([
                'sales_invoice_id' => $invoice->id,
                'sales_order_line_id' => $row['sales_order_line_id'] ?? null,
                'bag_product_id' => $resolved['bag_product_id'],
                'bag_product_variant_id' => $resolved['bag_product_variant_id'],
                'material_id' => $resolved['material_id'],
                'material_variant_id' => $resolved['material_variant_id'],
                'unit_of_measurement_id' => $resolved['unit_of_measurement_id'],
                'size_label' => $resolved['size_label'],
                'color_label' => $resolved['color_label'],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'discount_amount' => $discount,
                'tax_rate' => $taxRate,
                'tax_amount' => $calc['tax_amount'],
                'line_total' => $calc['line_total'],
                'sort_order' => $sort++,
            ]);
        }
    }

    private function refreshTotals(SalesInvoice $invoice): void
    {
        $lines = $invoice->lines()->get();
        $totals = $this->calculator->summarize($lines);

        $invoice->update([
            'subtotal' => $totals['subtotal'],
            'discount_total' => $totals['discount_total'],
            'tax_total' => $totals['tax_total'],
            'grand_total' => $totals['grand_total'],
        ]);
    }
}