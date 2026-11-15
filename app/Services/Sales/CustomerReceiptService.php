<?php

namespace App\Services\Sales;

use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\CustomerReceiptAllocation;
use App\Models\SalesInvoice;
use App\Models\User;
use App\Services\Accounting\AccountingIntegrationService;
use Illuminate\Support\Facades\DB;

class CustomerReceiptService
{
    public function __construct(
        private readonly SalesDocumentNumberGenerator $numbers,
        private readonly CustomerLedgerService $ledger,
        private readonly AccountingIntegrationService $accounting,
    ) {
    }

    /**
     * @param  list<array{sales_invoice_id: int, amount: float}>  $allocations
     */
    public function record(Customer $customer, array $header, array $allocations, User $actor): CustomerReceipt
    {
        $receiptAmount = round((float) $header['amount'], 2);
        $allocated = round(collect($allocations)->sum(fn ($row) => (float) $row['amount']), 2);

        if (abs($receiptAmount - $allocated) > 0.01) {
            throw new SalesException('Receipt amount must match allocation total.');
        }

        if ($receiptAmount <= 0) {
            throw new SalesException('Receipt amount must be positive.');
        }

        return DB::transaction(function () use ($customer, $header, $allocations, $receiptAmount, $actor) {
            $receipt = CustomerReceipt::query()->create([
                'receipt_number' => $this->numbers->next('customer_receipt'),
                'customer_id' => $customer->id,
                'receipt_date' => $header['receipt_date'],
                'amount' => $receiptAmount,
                'payment_method' => $header['payment_method'] ?? 'cash',
                'treasury_account_id' => $header['treasury_account_id'] ?? null,
                'reference' => $header['reference'] ?? null,
                'notes' => $header['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            foreach ($allocations as $row) {
                $invoice = SalesInvoice::query()
                    ->whereKey((int) $row['sales_invoice_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($invoice->customer_id !== $customer->id) {
                    throw new SalesException('Invoice does not belong to this customer.');
                }

                if (! in_array($invoice->status, ['posted', 'partially_paid'], true)) {
                    throw new SalesException('Invoice must be posted before receipt.');
                }

                $allocAmount = round((float) $row['amount'], 2);
                if ($allocAmount > $invoice->outstandingAmount() + 0.01) {
                    throw new SalesException("Allocation exceeds outstanding on {$invoice->invoice_number}.");
                }

                CustomerReceiptAllocation::query()->create([
                    'customer_receipt_id' => $receipt->id,
                    'sales_invoice_id' => $invoice->id,
                    'amount' => $allocAmount,
                ]);

                $newPaid = round((float) $invoice->amount_paid + $allocAmount, 2);
                $status = $newPaid >= (float) $invoice->grand_total - 0.01 ? 'paid' : 'partially_paid';

                $invoice->update([
                    'amount_paid' => $newPaid,
                    'status' => $status,
                ]);
            }

            $this->ledger->recordReceipt(
                customer: $customer->fresh(),
                entryDate: $header['receipt_date'],
                receiptId: $receipt->id,
                receiptNumber: $receipt->receipt_number,
                amount: $receiptAmount,
                actor: $actor,
            );

            $this->accounting->postCustomerReceipt($receipt->fresh(), $actor);

            return $receipt->fresh(['allocations.salesInvoice']);
        });
    }
}