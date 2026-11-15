<?php

namespace App\Services\Accounting;

use App\Models\TreasuryAccount;
use App\Models\TreasuryMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TreasuryService
{
    public function __construct(
        private readonly AccountingDocumentNumberGenerator $numbers,
        private readonly JournalEntryService $journals,
    ) {
    }

    public function resolveForPaymentMethod(?string $paymentMethod = null): TreasuryAccount
    {
        $code = ($paymentMethod === 'bank' || $paymentMethod === 'cheque')
            ? config('accounting.treasury_account_codes.default_bank')
            : config('accounting.treasury_account_codes.default_cash');

        return TreasuryAccount::query()->where('code', $code)->where('status', 'active')->firstOrFail();
    }

    public function recordCashMovement(
        TreasuryAccount $account,
        string $movementType,
        string $movementDate,
        float $amount,
        string $description,
        ?string $sourceType,
        ?int $sourceId,
        User $actor,
        ?TreasuryAccount $counterAccount = null,
    ): TreasuryMovement {
        $amount = round($amount, 2);
        if ($amount <= 0) {
            throw new AccountingException('Treasury movement amount must be positive.');
        }

        return DB::transaction(function () use ($account, $movementType, $movementDate, $amount, $description, $sourceType, $sourceId, $actor, $counterAccount) {
            $lines = match ($movementType) {
                'receipt', 'deposit' => [
                    ['account_code' => $account->glAccount->code, 'debit' => $amount],
                    ['account_code' => config('accounting.account_codes.owner_equity'), 'credit' => $amount],
                ],
                'payment', 'withdrawal' => [
                    ['account_code' => config('accounting.account_codes.owner_equity'), 'debit' => $amount],
                    ['account_code' => $account->glAccount->code, 'credit' => $amount],
                ],
                'transfer' => $this->transferLines($account, $counterAccount, $amount),
                default => throw new AccountingException("Unknown movement type: {$movementType}"),
            };

            if ($movementType === 'transfer' && ! $counterAccount) {
                throw new AccountingException('Transfer requires a counter treasury account.');
            }

            $journal = $this->journals->postBalanced(
                entryDate: $movementDate,
                sourceType: $sourceType,
                sourceId: $sourceId,
                description: $description,
                lines: $lines,
                actor: $actor,
            );

            $movement = TreasuryMovement::query()->create([
                'movement_number' => $this->numbers->next('treasury_movement'),
                'treasury_account_id' => $account->id,
                'counter_treasury_account_id' => $counterAccount?->id,
                'movement_type' => $movementType,
                'movement_date' => $movementDate,
                'amount' => $amount,
                'notes' => $description,
                'gl_journal_entry_id' => $journal->id,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'created_by' => $actor->id,
            ]);

            if ($movementType === 'transfer' && $counterAccount) {
                $account->decrement('current_balance', $amount);
                $counterAccount->increment('current_balance', $amount);
            } elseif (in_array($movementType, ['receipt', 'deposit'], true)) {
                $account->increment('current_balance', $amount);
            } elseif (in_array($movementType, ['payment', 'withdrawal'], true)) {
                $account->decrement('current_balance', $amount);
            }

            return $movement->fresh();
        });
    }

    /**
     * @return list<array{account_code: string, debit?: float, credit?: float}>
     */
    private function transferLines(TreasuryAccount $from, ?TreasuryAccount $to, float $amount): array
    {
        if (! $to) {
            throw new AccountingException('Transfer destination missing.');
        }

        return [
            ['account_code' => $to->glAccount->code, 'debit' => $amount],
            ['account_code' => $from->glAccount->code, 'credit' => $amount],
        ];
    }
}