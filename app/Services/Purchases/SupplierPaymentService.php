<?php

namespace App\Services\Purchases;

use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\SupplierPaymentAllocation;
use App\Models\User;
use App\Services\Accounting\AccountingIntegrationService;
use Illuminate\Support\Facades\DB;

class SupplierPaymentService
{
    public function __construct(
        private readonly PurchaseDocumentNumberGenerator $numbers,
        private readonly SupplierLedgerService $ledger,
        private readonly AccountingIntegrationService $accounting,
    ) {
    }

    /**
     * @param  list<array{purchase_invoice_id: int, amount: float}>  $allocations
     */
    public function record(Supplier $supplier, array $header, array $allocations, User $actor): SupplierPayment
    {
        $paymentAmount = round((float) $header['amount'], 2);
        $allocated = round(collect($allocations)->sum(fn ($row) => (float) $row['amount']), 2);

        if (abs($paymentAmount - $allocated) > 0.01) {
            throw new PurchaseException('Payment amount must match allocation total.');
        }

        if ($paymentAmount <= 0) {
            throw new PurchaseException('Payment amount must be positive.');
        }

        return DB::transaction(function () use ($supplier, $header, $allocations, $paymentAmount, $actor) {
            $payment = SupplierPayment::query()->create([
                'payment_number' => $this->numbers->next('supplier_payment'),
                'supplier_id' => $supplier->id,
                'payment_date' => $header['payment_date'],
                'amount' => $paymentAmount,
                'payment_method' => $header['payment_method'] ?? 'cash',
                'treasury_account_id' => $header['treasury_account_id'] ?? null,
                'reference' => $header['reference'] ?? null,
                'notes' => $header['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($allocations as $row) {
                $invoice = PurchaseInvoice::query()
                    ->whereKey((int) $row['purchase_invoice_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($invoice->supplier_id !== $supplier->id) {
                    throw new PurchaseException('Invoice does not belong to this supplier.');
                }

                if (! in_array($invoice->status, ['posted', 'partially_paid'], true)) {
                    throw new PurchaseException('Invoice must be posted before payment.');
                }

                $allocAmount = round((float) $row['amount'], 2);
                if ($allocAmount > $invoice->outstandingAmount() + 0.01) {
                    throw new PurchaseException("Allocation exceeds outstanding on {$invoice->invoice_number}.");
                }

                SupplierPaymentAllocation::query()->create([
                    'supplier_payment_id' => $payment->id,
                    'purchase_invoice_id' => $invoice->id,
                    'amount' => $allocAmount,
                ]);

                $newPaid = round((float) $invoice->amount_paid + $allocAmount, 2);
                $status = $newPaid >= (float) $invoice->grand_total - 0.01 ? 'paid' : 'partially_paid';

                $invoice->update([
                    'amount_paid' => $newPaid,
                    'status' => $status,
                ]);
            }

            $this->ledger->recordPayment(
                supplier: $supplier->fresh(),
                entryDate: $header['payment_date'],
                paymentId: $payment->id,
                paymentNumber: $payment->payment_number,
                amount: $paymentAmount,
                actor: $actor,
            );

            $this->accounting->postSupplierPayment($payment->fresh(), $actor);

            return $payment->fresh(['allocations.purchaseInvoice']);
        });
    }
}