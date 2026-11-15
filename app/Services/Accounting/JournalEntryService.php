<?php

namespace App\Services\Accounting;

use App\Models\GlAccount;
use App\Models\GlJournalEntry;
use App\Models\GlJournalLine;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class JournalEntryService
{
    public function __construct(
        private readonly AccountingDocumentNumberGenerator $numbers,
    ) {
    }

    /**
     * @param  list<array{account_code: string, debit?: float, credit?: float, memo?: string|null}>  $lines
     */
    public function postBalanced(
        string $entryDate,
        ?string $sourceType,
        ?int $sourceId,
        string $description,
        array $lines,
        User $actor,
    ): GlJournalEntry {
        if (! config('accounting.enabled', true)) {
            throw new AccountingException('Accounting is disabled.');
        }

        if ($sourceType !== null && $sourceId !== null) {
            $existing = GlJournalEntry::query()
                ->where('source_type', $sourceType)
                ->where('source_id', $sourceId)
                ->whereIn('status', ['posted', 'reversed'])
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $normalized = $this->normalizeLines($lines);
        $totalDebit = round(collect($normalized)->sum('debit'), 2);
        $totalCredit = round(collect($normalized)->sum('credit'), 2);

        if (abs($totalDebit - $totalCredit) > 0.009) {
            throw new AccountingException("Journal out of balance: debit {$totalDebit} vs credit {$totalCredit}.");
        }

        if ($totalDebit <= 0) {
            throw new AccountingException('Journal must have a positive total.');
        }

        return DB::transaction(function () use ($entryDate, $sourceType, $sourceId, $description, $normalized, $totalDebit, $totalCredit, $actor) {
            $entry = GlJournalEntry::query()->create([
                'entry_number' => $this->numbers->next('journal'),
                'entry_date' => $entryDate,
                'status' => 'posted',
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'description' => $description,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'posted_at' => now(),
                'posted_by' => $actor->id,
                'created_by' => $actor->id,
            ]);

            $sort = 0;
            foreach ($normalized as $row) {
                $account = GlAccount::query()->where('code', $row['account_code'])->firstOrFail();

                GlJournalLine::query()->create([
                    'gl_journal_entry_id' => $entry->id,
                    'gl_account_id' => $account->id,
                    'debit' => $row['debit'],
                    'credit' => $row['credit'],
                    'memo' => $row['memo'] ?? null,
                    'sort_order' => $sort++,
                ]);
            }

            return $entry->fresh('lines.account');
        });
    }

    public function reverse(GlJournalEntry $original, string $reversalDate, User $actor, ?string $reason = null): GlJournalEntry
    {
        if (! $original->isPosted()) {
            throw new AccountingException('Only posted entries can be reversed.');
        }

        $reversalLines = [];
        foreach ($original->lines as $line) {
            $reversalLines[] = [
                'account_code' => $line->account->code,
                'debit' => (float) $line->credit,
                'credit' => (float) $line->debit,
                'memo' => $reason ?? "Reversal of {$original->entry_number}",
            ];
        }

        return DB::transaction(function () use ($original, $reversalDate, $reversalLines, $actor, $reason) {
            $reversal = $this->postBalanced(
                entryDate: $reversalDate,
                sourceType: $original->source_type ? "{$original->source_type}_reversal" : null,
                sourceId: $original->source_id,
                description: $reason ?? "Reversal of {$original->entry_number}",
                lines: $reversalLines,
                actor: $actor,
            );

            $reversal->update(['reversal_of_id' => $original->id]);
            $original->update(['status' => 'reversed']);

            return $reversal;
        });
    }

    /**
     * @param  list<array{account_code: string, debit?: float, credit?: float, memo?: string|null}>  $lines
     * @return list<array{account_code: string, debit: float, credit: float, memo?: string|null}>
     */
    private function normalizeLines(array $lines): array
    {
        $out = [];

        foreach ($lines as $line) {
            $debit = round((float) ($line['debit'] ?? 0), 2);
            $credit = round((float) ($line['credit'] ?? 0), 2);

            if ($debit > 0 && $credit > 0) {
                throw new AccountingException('A journal line cannot have both debit and credit.');
            }

            if ($debit <= 0 && $credit <= 0) {
                continue;
            }

            $out[] = [
                'account_code' => $line['account_code'],
                'debit' => $debit,
                'credit' => $credit,
                'memo' => $line['memo'] ?? null,
            ];
        }

        return $out;
    }
}