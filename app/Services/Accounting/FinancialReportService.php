<?php

namespace App\Services\Accounting;

use App\Models\GlAccount;
use App\Models\GlJournalEntry;
use App\Models\GlJournalLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * @return Collection<int, object{account: GlAccount, debit: float, credit: float, balance: float}>
     */
    public function trialBalance(?string $from = null, ?string $to = null): Collection
    {
        $rows = $this->aggregateByAccount($from, $to);
        $accounts = $this->accountsFor($rows);

        return $rows->map(function ($row) use ($accounts) {
            $account = $accounts->get((int) $row->gl_account_id) ?? GlAccount::query()->findOrFail($row->gl_account_id);
            $balance = $account->normal_balance === 'debit'
                ? (float) $row->debit - (float) $row->credit
                : (float) $row->credit - (float) $row->debit;

            return (object) [
                'account' => $account,
                'debit' => (float) $row->debit,
                'credit' => (float) $row->credit,
                'balance' => round($balance, 2),
            ];
        })->sortBy(fn ($r) => $r->account->code)->values();
    }

    /**
     * @return array{revenue: float, expenses: float, net_profit: float, lines: Collection}
     */
    public function profitAndLoss(?string $from = null, ?string $to = null): array
    {
        $rows = $this->aggregateByAccount($from, $to);

        return $this->profitAndLossFromAggregates($rows, $this->accountsFor($rows));
    }

    /**
     * @return array{assets: float, liabilities: float, equity: float, balanced: bool, lines: Collection}
     */
    public function balanceSheet(?string $asOf = null): array
    {
        $rows = $this->aggregateByAccount(null, $asOf);
        $accounts = $this->accountsFor($rows);
        $assets = 0.0;
        $liabilities = 0.0;
        $equity = 0.0;
        $lines = collect();

        foreach ($rows as $row) {
            $account = $accounts->get((int) $row->gl_account_id);
            if (! $account) {
                continue;
            }

            $balance = match ($account->type) {
                'asset' => (float) $row->debit - (float) $row->credit,
                'liability', 'equity' => (float) $row->credit - (float) $row->debit,
                default => 0.0,
            };

            if (abs($balance) < 0.009) {
                continue;
            }

            match ($account->type) {
                'asset' => $assets += $balance,
                'liability' => $liabilities += $balance,
                'equity' => $equity += $balance,
                default => null,
            };

            $lines->push(['account' => $account, 'amount' => round($balance, 2)]);
        }

        $pnl = $this->profitAndLossFromAggregates($rows, $accounts);
        $equity += $pnl['net_profit'];

        return [
            'assets' => round($assets, 2),
            'liabilities' => round($liabilities, 2),
            'equity' => round($equity, 2),
            'retained_profit' => $pnl['net_profit'],
            'balanced' => abs($assets - ($liabilities + $equity)) < 0.05,
            'lines' => $lines,
        ];
    }

    /**
     * @return Collection<int, GlJournalLine>
     */
    public function generalLedger(int $glAccountId, ?string $from = null, ?string $to = null): Collection
    {
        return GlJournalLine::query()
            ->with(['entry', 'account'])
            ->join('gl_journal_entries', 'gl_journal_entries.id', '=', 'gl_journal_lines.gl_journal_entry_id')
            ->where('gl_journal_lines.gl_account_id', $glAccountId)
            ->where('gl_journal_entries.status', 'posted')
            ->when($from, fn ($q) => $q->whereDate('gl_journal_entries.entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('gl_journal_entries.entry_date', '<=', $to))
            ->orderBy('gl_journal_entries.entry_date')
            ->orderBy('gl_journal_lines.id')
            ->select('gl_journal_lines.*')
            ->get();
    }

    private function aggregateByAccount(?string $from, ?string $to): Collection
    {
        return GlJournalLine::query()
            ->select([
                'gl_journal_lines.gl_account_id',
                DB::raw('SUM(gl_journal_lines.debit) as debit'),
                DB::raw('SUM(gl_journal_lines.credit) as credit'),
            ])
            ->join('gl_journal_entries', 'gl_journal_entries.id', '=', 'gl_journal_lines.gl_journal_entry_id')
            ->where('gl_journal_entries.status', 'posted')
            ->when($from, fn ($q) => $q->whereDate('gl_journal_entries.entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('gl_journal_entries.entry_date', '<=', $to))
            ->groupBy('gl_journal_lines.gl_account_id')
            ->get();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return Collection<int|string, GlAccount>
     */
    private function accountsFor(Collection $rows): Collection
    {
        $ids = $rows->pluck('gl_account_id')->unique()->filter()->values();

        if ($ids->isEmpty()) {
            return collect();
        }

        return GlAccount::query()->whereIn('id', $ids)->get()->keyBy(fn (GlAccount $account) => (int) $account->id);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  Collection<int|string, GlAccount>  $accounts
     * @return array{revenue: float, expenses: float, net_profit: float, lines: Collection}
     */
    private function profitAndLossFromAggregates(Collection $rows, Collection $accounts): array
    {
        $revenue = 0.0;
        $expenses = 0.0;
        $lines = collect();

        foreach ($rows as $row) {
            $account = $accounts->get((int) $row->gl_account_id);
            if (! $account) {
                continue;
            }

            if ($account->type === 'revenue') {
                $amount = (float) $row->credit - (float) $row->debit;
                $revenue += $amount;
                $lines->push(['account' => $account, 'amount' => round($amount, 2)]);
            }

            if ($account->type === 'expense') {
                $amount = (float) $row->debit - (float) $row->credit;
                $expenses += $amount;
                $lines->push(['account' => $account, 'amount' => round($amount, 2)]);
            }
        }

        return [
            'revenue' => round($revenue, 2),
            'expenses' => round($expenses, 2),
            'net_profit' => round($revenue - $expenses, 2),
            'lines' => $lines,
        ];
    }
}