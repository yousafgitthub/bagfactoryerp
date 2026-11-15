<?php

namespace App\Services\Accounting;

use App\Models\CompanyLoan;
use App\Models\CompanyLoanRepayment;
use App\Models\TreasuryAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyLoanService
{
    public function __construct(
        private readonly AccountingDocumentNumberGenerator $numbers,
        private readonly AccountingIntegrationService $integration,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): CompanyLoan
    {
        return DB::transaction(function () use ($data, $actor) {
            $principal = round((float) $data['principal'], 2);
            $treasury = TreasuryAccount::query()->findOrFail($data['treasury_account_id']);

            $loan = CompanyLoan::query()->create([
                'loan_number' => $this->numbers->next('company_loan'),
                'direction' => $data['direction'],
                'counterparty_name' => $data['counterparty_name'],
                'principal' => $principal,
                'outstanding_principal' => $principal,
                'start_date' => $data['start_date'],
                'maturity_date' => $data['maturity_date'] ?? null,
                'status' => 'active',
                'interest_rate' => (float) ($data['interest_rate'] ?? 0),
                'notes' => $data['notes'] ?? null,
                'created_by' => $actor->id,
            ]);

            $journal = $this->integration->postCompanyLoan($loan, $treasury, $actor);
            if ($journal) {
                $loan->update(['gl_journal_entry_id' => $journal->id]);
            }

            return $loan->fresh(['repayments']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function recordRepayment(CompanyLoan $loan, array $data, User $actor): CompanyLoanRepayment
    {
        if ($loan->status !== 'active') {
            throw new AccountingException('Loan is not active.');
        }

        $principal = round((float) ($data['principal_amount'] ?? 0), 2);
        $interest = round((float) ($data['interest_amount'] ?? 0), 2);
        $total = round($principal + $interest, 2);

        if ($total <= 0) {
            throw new AccountingException('Repayment total must be positive.');
        }

        if ($principal > (float) $loan->outstanding_principal + 0.01) {
            throw new AccountingException('Principal exceeds outstanding loan balance.');
        }

        return DB::transaction(function () use ($loan, $data, $actor, $principal, $interest, $total) {
            $treasury = TreasuryAccount::query()->findOrFail($data['treasury_account_id']);

            $repayment = CompanyLoanRepayment::query()->create([
                'company_loan_id' => $loan->id,
                'repayment_date' => $data['repayment_date'],
                'principal_amount' => $principal,
                'interest_amount' => $interest,
                'total_amount' => $total,
                'treasury_account_id' => $treasury->id,
                'created_by' => $actor->id,
            ]);

            $journal = $this->integration->postCompanyLoanRepayment($loan, $repayment, $treasury, $actor);
            if ($journal) {
                $repayment->update(['gl_journal_entry_id' => $journal->id]);
            }

            $newOutstanding = max(0, round((float) $loan->outstanding_principal - $principal, 2));
            $loan->update([
                'outstanding_principal' => $newOutstanding,
                'status' => $newOutstanding <= 0.009 ? 'closed' : 'active',
            ]);

            return $repayment->fresh(['loan']);
        });
    }
}