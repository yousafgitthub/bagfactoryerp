<?php

namespace App\Services\Accounting;

use App\Models\CompanyLoan;
use App\Models\CompanyLoanRepayment;
use App\Models\CustomerReceipt;
use App\Models\Expense;
use App\Models\GoodsReceipt;
use App\Models\GlJournalEntry;
use App\Models\InventoryLedgerEntry;
use App\Models\PayrollPayment;
use App\Models\PayrollRun;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SupplierPayment;
use App\Models\TreasuryAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountingIntegrationService
{
    public function __construct(
        private readonly JournalEntryService $journals,
        private readonly TreasuryService $treasury,
    ) {
    }

    public function enabled(): bool
    {
        return config('accounting.enabled', true) && config('accounting.auto_post', true);
    }

    private function code(string $key): string
    {
        return (string) config("accounting.account_codes.{$key}");
    }

    public function postGoodsReceipt(GoodsReceipt $receipt, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $receipt->loadMissing('inventoryTransaction.ledgerEntries');
        $amount = round((float) InventoryLedgerEntry::query()
            ->where('inventory_transaction_id', $receipt->inventory_transaction_id)
            ->where('quantity', '>', 0)
            ->sum('total_value'), 2);

        if ($amount <= 0) {
            return null;
        }

        return $this->journals->postBalanced(
            entryDate: $receipt->receipt_date->toDateString(),
            sourceType: 'goods_receipt',
            sourceId: $receipt->id,
            description: "GRN {$receipt->receipt_number}",
            lines: [
                ['account_code' => $this->code('inventory'), 'debit' => $amount],
                ['account_code' => $this->code('grni'), 'credit' => $amount],
            ],
            actor: $actor,
        );
    }

    public function postPurchaseInvoice(PurchaseInvoice $invoice, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $amount = round((float) $invoice->grand_total, 2);
        if ($amount <= 0) {
            return null;
        }

        return $this->journals->postBalanced(
            entryDate: $invoice->invoice_date->toDateString(),
            sourceType: 'purchase_invoice',
            sourceId: $invoice->id,
            description: "Purchase invoice {$invoice->invoice_number}",
            lines: [
                ['account_code' => $this->code('grni'), 'debit' => $amount],
                ['account_code' => $this->code('accounts_payable'), 'credit' => $amount],
            ],
            actor: $actor,
        );
    }

    public function postSupplierPayment(SupplierPayment $payment, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $amount = round((float) $payment->amount, 2);
        $treasury = $payment->treasury_account_id
            ? TreasuryAccount::query()->findOrFail($payment->treasury_account_id)
            : $this->treasury->resolveForPaymentMethod($payment->payment_method);

        $entry = $this->journals->postBalanced(
            entryDate: $payment->payment_date->toDateString(),
            sourceType: 'supplier_payment',
            sourceId: $payment->id,
            description: "Supplier payment {$payment->payment_number}",
            lines: [
                ['account_code' => $this->code('accounts_payable'), 'debit' => $amount],
                ['account_code' => $treasury->glAccount->code, 'credit' => $amount],
            ],
            actor: $actor,
        );

        $treasury->decrement('current_balance', $amount);

        return $entry;
    }

    public function postSalesInvoice(SalesInvoice $invoice, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $revenue = round((float) $invoice->grand_total, 2);
        $cogs = round((float) $invoice->cogs_total, 2);

        $lines = [
            ['account_code' => $this->code('accounts_receivable'), 'debit' => $revenue],
            ['account_code' => $this->code('sales_revenue'), 'credit' => $revenue],
        ];

        if ($cogs > 0) {
            $lines[] = ['account_code' => $this->code('cogs'), 'debit' => $cogs];
            $lines[] = ['account_code' => $this->code('inventory'), 'credit' => $cogs];
        }

        return $this->journals->postBalanced(
            entryDate: $invoice->invoice_date->toDateString(),
            sourceType: 'sales_invoice',
            sourceId: $invoice->id,
            description: "Sales invoice {$invoice->invoice_number}",
            lines: $lines,
            actor: $actor,
        );
    }

    public function postCustomerReceipt(CustomerReceipt $receipt, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $amount = round((float) $receipt->amount, 2);
        $treasury = $receipt->treasury_account_id
            ? TreasuryAccount::query()->findOrFail($receipt->treasury_account_id)
            : $this->treasury->resolveForPaymentMethod($receipt->payment_method);

        $entry = $this->journals->postBalanced(
            entryDate: $receipt->receipt_date->toDateString(),
            sourceType: 'customer_receipt',
            sourceId: $receipt->id,
            description: "Customer receipt {$receipt->receipt_number}",
            lines: [
                ['account_code' => $treasury->glAccount->code, 'debit' => $amount],
                ['account_code' => $this->code('accounts_receivable'), 'credit' => $amount],
            ],
            actor: $actor,
        );

        $treasury->increment('current_balance', $amount);

        return $entry;
    }

    public function postSalesReturn(SalesReturn $salesReturn, float $creditAmount, float $cogsAmount, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $lines = [];
        $creditAmount = round($creditAmount, 2);
        $cogsAmount = round($cogsAmount, 2);

        if ($creditAmount > 0) {
            $lines[] = ['account_code' => $this->code('sales_revenue'), 'debit' => $creditAmount];
            $lines[] = ['account_code' => $this->code('accounts_receivable'), 'credit' => $creditAmount];
        }

        if ($cogsAmount > 0) {
            $lines[] = ['account_code' => $this->code('inventory'), 'debit' => $cogsAmount];
            $lines[] = ['account_code' => $this->code('cogs'), 'credit' => $cogsAmount];
        }

        if ($lines === []) {
            return null;
        }

        return $this->journals->postBalanced(
            entryDate: $salesReturn->return_date->toDateString(),
            sourceType: 'sales_return',
            sourceId: $salesReturn->id,
            description: "Sales return {$salesReturn->return_number}",
            lines: $lines,
            actor: $actor,
        );
    }

    public function postPurchaseReturn(PurchaseReturn $purchaseReturn, float $returnAmount, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $returnAmount = round($returnAmount, 2);
        if ($returnAmount <= 0) {
            return null;
        }

        return $this->journals->postBalanced(
            entryDate: $purchaseReturn->return_date->toDateString(),
            sourceType: 'purchase_return',
            sourceId: $purchaseReturn->id,
            description: "Purchase return {$purchaseReturn->return_number}",
            lines: [
                ['account_code' => $this->code('accounts_payable'), 'debit' => $returnAmount],
                ['account_code' => $this->code('inventory'), 'credit' => $returnAmount],
            ],
            actor: $actor,
        );
    }

    public function postPayrollAccrual(PayrollRun $run, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $run->loadMissing('records');
        $net = round((float) $run->records->sum('net_payable'), 2);
        if ($net <= 0) {
            return null;
        }

        return $this->journals->postBalanced(
            entryDate: ($run->period_end ?? $run->created_at)->toDateString(),
            sourceType: 'payroll_run',
            sourceId: $run->id,
            description: "Payroll accrual {$run->period_label}",
            lines: [
                ['account_code' => $this->code('payroll_expense'), 'debit' => $net],
                ['account_code' => $this->code('salaries_payable'), 'credit' => $net],
            ],
            actor: $actor,
        );
    }

    public function postPayrollPayment(PayrollPayment $payment, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $amount = round((float) $payment->amount, 2);
        $treasury = $payment->treasury_account_id
            ? TreasuryAccount::query()->findOrFail($payment->treasury_account_id)
            : $this->treasury->resolveForPaymentMethod($payment->payment_method);

        $entry = $this->journals->postBalanced(
            entryDate: $payment->payment_date->toDateString(),
            sourceType: 'payroll_payment',
            sourceId: $payment->id,
            description: 'Payroll salary payment',
            lines: [
                ['account_code' => $this->code('salaries_payable'), 'debit' => $amount],
                ['account_code' => $treasury->glAccount->code, 'credit' => $amount],
            ],
            actor: $actor,
        );

        $treasury->decrement('current_balance', $amount);

        return $entry;
    }

    public function postExpense(Expense $expense, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $expense->loadMissing('category.glAccount');
        $amount = round((float) $expense->amount, 2);
        $expenseCode = $expense->category?->glAccount?->code ?? $this->code('factory_expense');

        if ($expense->payment_status === 'unpaid') {
            return $this->journals->postBalanced(
                entryDate: $expense->expense_date->toDateString(),
                sourceType: 'expense',
                sourceId: $expense->id,
                description: "Expense bill {$expense->expense_number}",
                lines: [
                    ['account_code' => $expenseCode, 'debit' => $amount],
                    ['account_code' => $this->code('accrued_expenses'), 'credit' => $amount],
                ],
                actor: $actor,
            );
        }

        $treasury = $expense->treasury_account_id
            ? TreasuryAccount::query()->findOrFail($expense->treasury_account_id)
            : $this->treasury->resolveForPaymentMethod($expense->payment_method);

        $entry = $this->journals->postBalanced(
            entryDate: $expense->expense_date->toDateString(),
            sourceType: 'expense',
            sourceId: $expense->id,
            description: "Expense {$expense->expense_number}",
            lines: [
                ['account_code' => $expenseCode, 'debit' => $amount],
                ['account_code' => $treasury->glAccount->code, 'credit' => $amount],
            ],
            actor: $actor,
        );

        $treasury->decrement('current_balance', $amount);

        return $entry;
    }

    public function postCompanyLoan(CompanyLoan $loan, TreasuryAccount $treasury, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $principal = round((float) $loan->principal, 2);
        $lines = $loan->direction === 'received'
            ? [
                ['account_code' => $treasury->glAccount->code, 'debit' => $principal],
                ['account_code' => $this->code('loans_payable'), 'credit' => $principal],
            ]
            : [
                ['account_code' => $this->code('loans_receivable'), 'debit' => $principal],
                ['account_code' => $treasury->glAccount->code, 'credit' => $principal],
            ];

        $entry = $this->journals->postBalanced(
            entryDate: $loan->start_date->toDateString(),
            sourceType: 'company_loan',
            sourceId: $loan->id,
            description: "Company loan {$loan->loan_number}",
            lines: $lines,
            actor: $actor,
        );

        $delta = $loan->direction === 'received' ? $principal : -$principal;
        $treasury->increment('current_balance', $delta);

        return $entry;
    }

    public function postCompanyLoanRepayment(CompanyLoan $loan, CompanyLoanRepayment $repayment, TreasuryAccount $treasury, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $principal = round((float) $repayment->principal_amount, 2);
        $interest = round((float) $repayment->interest_amount, 2);
        $total = round((float) $repayment->total_amount, 2);

        $lines = [];
        if ($loan->direction === 'received') {
            if ($principal > 0) {
                $lines[] = ['account_code' => $this->code('loans_payable'), 'debit' => $principal];
            }
            if ($interest > 0) {
                $lines[] = ['account_code' => $this->code('interest_expense'), 'debit' => $interest];
            }
            $lines[] = ['account_code' => $treasury->glAccount->code, 'credit' => $total];
        } else {
            $lines[] = ['account_code' => $treasury->glAccount->code, 'debit' => $total];
            if ($principal > 0) {
                $lines[] = ['account_code' => $this->code('loans_receivable'), 'credit' => $principal];
            }
            if ($interest > 0) {
                $lines[] = ['account_code' => $this->code('interest_expense'), 'credit' => $interest];
            }
        }

        $entry = $this->journals->postBalanced(
            entryDate: $repayment->repayment_date->toDateString(),
            sourceType: 'company_loan_repayment',
            sourceId: $repayment->id,
            description: "Loan repayment {$loan->loan_number}",
            lines: $lines,
            actor: $actor,
        );

        $delta = $loan->direction === 'received' ? -$total : $total;
        $treasury->increment('current_balance', $delta);

        return $entry;
    }

    public function postCashSaleReceipt(SalesInvoice $invoice, CustomerReceipt $receipt, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $amount = round((float) $receipt->amount, 2);
        $treasury = $receipt->treasury_account_id
            ? TreasuryAccount::query()->findOrFail($receipt->treasury_account_id)
            : $this->treasury->resolveForPaymentMethod($receipt->payment_method);

        $entry = $this->journals->postBalanced(
            entryDate: $receipt->receipt_date->toDateString(),
            sourceType: 'cash_sale_receipt',
            sourceId: $receipt->id,
            description: "Cash sale {$invoice->invoice_number}",
            lines: [
                ['account_code' => $treasury->glAccount->code, 'debit' => $amount],
                ['account_code' => $this->code('accounts_receivable'), 'credit' => $amount],
            ],
            actor: $actor,
        );

        $treasury->increment('current_balance', $amount);

        return $entry;
    }

    public function postProductionCapitalization(string $sourceType, int $sourceId, string $entryDate, float $amount, string $description, User $actor): ?GlJournalEntry
    {
        if (! $this->enabled()) {
            return null;
        }

        $amount = round($amount, 2);
        if ($amount <= 0) {
            return null;
        }

        return $this->journals->postBalanced(
            entryDate: $entryDate,
            sourceType: $sourceType,
            sourceId: $sourceId,
            description: $description,
            lines: [
                ['account_code' => $this->code('inventory'), 'debit' => $amount],
                ['account_code' => $this->code('payroll_expense'), 'credit' => $amount],
            ],
            actor: $actor,
        );
    }
}