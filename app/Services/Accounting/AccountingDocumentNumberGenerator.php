<?php

namespace App\Services\Accounting;

use Illuminate\Support\Facades\DB;

class AccountingDocumentNumberGenerator
{
    public function next(string $type): string
    {
        $prefix = config("accounting.document_prefixes.{$type}", strtoupper($type));

        [$table, $column] = match ($type) {
            'journal' => ['gl_journal_entries', 'entry_number'],
            'treasury_movement' => ['treasury_movements', 'movement_number'],
            'expense' => ['expenses', 'expense_number'],
            'company_loan' => ['company_loans', 'loan_number'],
            default => throw new AccountingException("Unknown document type: {$type}"),
        };

        $year = now()->format('Y');

        return DB::transaction(function () use ($table, $column, $prefix, $year) {
            $pattern = "{$prefix}-{$year}-%";
            $last = DB::table($table)
                ->where($column, 'like', $pattern)
                ->lockForUpdate()
                ->orderByDesc($column)
                ->value($column);

            $sequence = 1;
            if ($last && preg_match('/-(\d+)$/', (string) $last, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return sprintf('%s-%s-%04d', $prefix, $year, $sequence);
        });
    }
}