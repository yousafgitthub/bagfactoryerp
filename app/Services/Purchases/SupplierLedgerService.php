<?php

namespace App\Services\Purchases;

use App\Models\Supplier;
use App\Models\SupplierLedgerEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SupplierLedgerService
{
    public function recordOpening(Supplier $supplier, User $actor): void
    {
        $balance = (float) $supplier->opening_balance;
        if ($balance == 0.0) {
            $supplier->update(['current_balance' => 0]);

            return;
        }

        DB::transaction(function () use ($supplier, $balance, $actor) {
            $this->append(
                supplier: $supplier,
                entryDate: now()->toDateString(),
                entryType: 'opening_balance',
                referenceType: Supplier::class,
                referenceId: $supplier->id,
                documentNumber: $supplier->code,
                description: 'Opening balance',
                debit: $balance < 0 ? abs($balance) : 0,
                credit: $balance > 0 ? abs($balance) : 0,
                actor: $actor,
            );
        });
    }

    public function recordInvoice(
        Supplier $supplier,
        string $entryDate,
        int $invoiceId,
        string $invoiceNumber,
        float $amount,
        User $actor,
    ): SupplierLedgerEntry {
        return $this->append(
            supplier: $supplier,
            entryDate: $entryDate,
            entryType: 'purchase_invoice',
            referenceType: 'purchase_invoice',
            referenceId: $invoiceId,
            documentNumber: $invoiceNumber,
            description: 'Purchase invoice',
            debit: 0,
            credit: round($amount, 2),
            actor: $actor,
        );
    }

    public function recordPayment(
        Supplier $supplier,
        string $entryDate,
        int $paymentId,
        string $paymentNumber,
        float $amount,
        User $actor,
    ): SupplierLedgerEntry {
        return $this->append(
            supplier: $supplier,
            entryDate: $entryDate,
            entryType: 'supplier_payment',
            referenceType: 'supplier_payment',
            referenceId: $paymentId,
            documentNumber: $paymentNumber,
            description: 'Supplier payment',
            debit: round($amount, 2),
            credit: 0,
            actor: $actor,
        );
    }

    public function recordReturn(
        Supplier $supplier,
        string $entryDate,
        int $returnId,
        string $returnNumber,
        float $amount,
        User $actor,
    ): SupplierLedgerEntry {
        return $this->append(
            supplier: $supplier,
            entryDate: $entryDate,
            entryType: 'purchase_return',
            referenceType: 'purchase_return',
            referenceId: $returnId,
            documentNumber: $returnNumber,
            description: 'Purchase return',
            debit: round($amount, 2),
            credit: 0,
            actor: $actor,
        );
    }

    private function append(
        Supplier $supplier,
        string $entryDate,
        string $entryType,
        ?string $referenceType,
        ?int $referenceId,
        ?string $documentNumber,
        ?string $description,
        float $debit,
        float $credit,
        User $actor,
    ): SupplierLedgerEntry {
        return DB::transaction(function () use (
            $supplier,
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
            $locked = Supplier::query()->whereKey($supplier->id)->lockForUpdate()->firstOrFail();
            $previous = (float) $locked->current_balance;
            $next = round($previous + $credit - $debit, 2);

            $entry = SupplierLedgerEntry::query()->create([
                'supplier_id' => $locked->id,
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