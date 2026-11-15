<?php

namespace App\Services\Accounting;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(
        private readonly AccountingDocumentNumberGenerator $numbers,
        private readonly AccountingIntegrationService $integration,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Expense
    {
        return DB::transaction(function () use ($data, $actor) {
            $expense = Expense::query()->create([
                'expense_number' => $this->numbers->next('expense'),
                'expense_category_id' => $data['expense_category_id'],
                'expense_date' => $data['expense_date'],
                'amount' => round((float) $data['amount'], 2),
                'payment_status' => $data['payment_status'] ?? 'paid',
                'payment_method' => $data['payment_method'] ?? null,
                'treasury_account_id' => $data['treasury_account_id'] ?? null,
                'description' => $data['description'] ?? null,
                'attachment_path' => $data['attachment_path'] ?? null,
                'created_by' => $actor->id,
            ]);

            $journal = $this->integration->postExpense($expense, $actor);
            if ($journal) {
                $expense->update(['gl_journal_entry_id' => $journal->id]);
            }

            return $expense->fresh(['category', 'treasuryAccount', 'journalEntry']);
        });
    }
}