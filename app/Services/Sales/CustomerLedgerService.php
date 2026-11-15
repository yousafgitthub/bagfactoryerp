<?php

namespace App\Services\Sales;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerLedgerService
{
    public function recordOpening(Customer $customer, User $actor): void
    {
        $balance = (float) $customer->opening_balance;
        if ($balance == 0.0) {
            $customer->update(['current_balance' => 0]);

            return;
        }

        DB::transaction(function () use ($customer, $balance, $actor) {
            $this->append(
                customer: $customer,
                entryDate: now()->toDateString(),
                entryType: 'opening_balance',
                referenceType: Customer::class,
                referenceId: $customer->id,
                documentNumber: $customer->code,
                description: 'Opening balance',
                debit: $balance > 0 ? abs($balance) : 0,
                credit: $balance < 0 ? abs($balance) : 0,
                actor: $actor,
            );
        });
    }

    public function recordInvoice(
        Customer $customer,
        string $entryDate,
        int $invoiceId,
        string $invoiceNumber,
        float $amount,
        User $actor,
    ): CustomerLedgerEntry {
        return $this->append(
            customer: $customer,
            entryDate: $entryDate,
            entryType: 'sales_invoice',
            referenceType: 'sales_invoice',
            referenceId: $invoiceId,
            documentNumber: $invoiceNumber,
            description: 'Sales invoice',
            debit: round($amount, 2),
            credit: 0,
            actor: $actor,
        );
    }

    public function recordReceipt(
        Customer $customer,
        string $entryDate,
        int $receiptId,
        string $receiptNumber,
        float $amount,
        User $actor,
    ): CustomerLedgerEntry {
        return $this->append(
            customer: $customer,
            entryDate: $entryDate,
            entryType: 'customer_receipt',
            referenceType: 'customer_receipt',
            referenceId: $receiptId,
            documentNumber: $receiptNumber,
            description: 'Customer receipt',
            debit: 0,
            credit: round($amount, 2),
            actor: $actor,
        );
    }

    public function recordReturn(
        Customer $customer,
        string $entryDate,
        int $returnId,
        string $returnNumber,
        float $amount,
        User $actor,
    ): CustomerLedgerEntry {
        return $this->append(
            customer: $customer,
            entryDate: $entryDate,
            entryType: 'sales_return',
            referenceType: 'sales_return',
            referenceId: $returnId,
            documentNumber: $returnNumber,
            description: 'Sales return',
            debit: 0,
            credit: round($amount, 2),
            actor: $actor,
        );
    }

    private function append(
        Customer $customer,
        string $entryDate,
        string $entryType,
        ?string $referenceType,
        ?int $referenceId,
        ?string $documentNumber,
        ?string $description,
        float $debit,
        float $credit,
        User $actor,
    ): CustomerLedgerEntry {
        return DB::transaction(function () use (
            $customer,
            $entryDate,
            $entryType,
            $referenceType,
            $referenceId,
            $documentNumber,
            $description,
            $debit,
            $credit,
            $actor
        ) {
            $locked = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $previous = (float) $locked->current_balance;
            $next = round($previous + $debit - $credit, 2);

            $entry = CustomerLedgerEntry::query()->create([
                'customer_id' => $locked->id,
                'entry_date' => $entryDate,
                'entry_type' => $entryType,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'document_number' => $documentNumber,
                'description' => $description,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $next,
                'created_by' => $actor->id,
            ]);

            $locked->update(['current_balance' => $next]);

            return $entry;
        });
    }
}