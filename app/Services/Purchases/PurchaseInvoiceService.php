<?php

namespace App\Services\Purchases;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\SupplierLedgerEntry;
use App\Models\User;
use App\Services\Accounting\AccountingIntegrationService;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceService
{
    public function __construct(
        private readonly PurchaseDocumentNumberGenerator $numbers,
        private readonly PurchaseLineCalculator $calculator,
        private readonly SupplierLedgerService $ledger,
        private readonly AccountingIntegrationService $accounting,
    ) {
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    public function createDraft(array $header, array $lines, User $actor): PurchaseInvoice
    {
        return DB::transaction(function () use ($header, $lines, $actor) {
            $invoice = PurchaseInvoice::query()->create([
                'invoice_number' => $this->numbers->next('purchase_invoice'),
                'supplier_invoice_number' => $header['supplier_invoice_number'] ?? null,
                'supplier_id' => $header['supplier_id'],
                'purchase_order_id' => $header['purchase_order_id'] ?? null,
                'invoice_date' => $header['invoice_date'],
                'due_date' => $header['due_date'] ?? null,
                'status' => 'draft',
                'notes' => $header['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $this->syncLines($invoice, $lines);
            $this->refreshTotals($invoice);

            return $invoice->fresh(['lines.material', 'supplier']);
        });
    }

    /**
     * @param  list<array{purchase_order_line_id: int, quantity: float, goods_receipt_line_id?: int}>  $lines
     */
    public function createFromPurchaseOrder(PurchaseOrder $order, array $header, array $lines, User $actor): PurchaseInvoice
    {
        $order->load('lines');

        $header['supplier_id'] = $order->supplier_id;
        $header['purchase_order_id'] = $order->id;

        $normalized = [];
        foreach ($lines as $row) {
            $poLine = $order->lines->firstWhere('id', (int) $row['purchase_order_line_id']);
            if (! $poLine) {
                throw new PurchaseException('Invalid PO line on invoice.');
            }

            $qty = (float) $row['quantity'];
            if ($qty > $poLine->remainingInvoiceableQty() + 0.0001) {
                throw new PurchaseException('Invoice quantity exceeds received-not-invoiced quantity.');
            }

            $normalized[] = [
                'purchase_order_line_id' => $poLine->id,
                'goods_receipt_line_id' => $row['goods_receipt_line_id'] ?? null,
                'material_id' => $poLine->material_id,
                'material_variant_id' => $poLine->material_variant_id,
                'quantity' => $qty,
                'unit_price' => (float) $poLine->unit_price,
                'discount_amount' => (float) $poLine->discount_amount,
                'tax_rate' => (float) $poLine->tax_rate,
            ];
        }

        return $this->createDraft($header, $normalized, $actor);
    }

    public function post(PurchaseInvoice $invoice, User $actor): PurchaseInvoice
    {
        if (! $invoice->isDraft()) {
            throw new PurchaseException('Only draft invoices can be posted.');
        }

        return DB::transaction(function () use ($invoice, $actor) {
            $locked = PurchaseInvoice::query()->whereKey($invoice->id)->lockForUpdate()->with('lines', 'supplier')->firstOrFail();

            if ($locked->lines->isEmpty()) {
                throw new PurchaseException('Invoice has no lines.');
            }

            if (SupplierLedgerEntry::query()
                ->where('reference_type', 'purchase_invoice')
                ->where('reference_id', $locked->id)
                ->exists()) {
                throw new PurchaseException('Invoice already posted to supplier ledger.');
            }

            foreach ($locked->lines as $line) {
                if ($line->purchase_order_line_id) {
                    $poLine = PurchaseOrderLine::query()->lockForUpdate()->findOrFail($line->purchase_order_line_id);
                    if ((float) $line->quantity > $poLine->remainingInvoiceableQty() + 0.0001) {
                        throw new PurchaseException('Invoice quantity exceeds receivable balance on PO line.');
                    }
                    $poLine->increment('invoiced_qty', (float) $line->quantity);
                }
            }

            $locked->update([
                'status' => 'posted',
                'posted_at' => now(),
                'posted_by' => $actor->id,
            ]);

            $this->ledger->recordInvoice(
                supplier: $locked->supplier,
                entryDate: $locked->invoice_date->toDateString(),
                invoiceId: $locked->id,
                invoiceNumber: $locked->invoice_number,
                amount: (float) $locked->grand_total,
                actor: $actor,
            );

            $this->accounting->postPurchaseInvoice($locked->fresh(), $actor);

            return $locked->fresh();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     */
    private function syncLines(PurchaseInvoice $invoice, array $lines): void
    {
        $sort = 0;
        foreach ($lines as $row) {
            $qty = (float) $row['quantity'];
            $unitPrice = (float) $row['unit_price'];
            $discount = (float) ($row['discount_amount'] ?? 0);
            $taxRate = (float) ($row['tax_rate'] ?? 0);
            $calc = $this->calculator->compute($qty, $unitPrice, $discount, $taxRate);

            PurchaseInvoiceLine::query()->create([
                'purchase_invoice_id' => $invoice->id,
                'purchase_order_line_id' => $row['purchase_order_line_id'] ?? null,
                'goods_receipt_line_id' => $row['goods_receipt_line_id'] ?? null,
                'material_id' => $row['material_id'],
                'material_variant_id' => $row['material_variant_id'] ?? null,
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

    private function refreshTotals(PurchaseInvoice $invoice): void
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