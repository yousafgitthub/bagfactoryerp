<?php

namespace App\Services\Sales;

use Illuminate\Support\Facades\DB;

class SalesDocumentNumberGenerator
{
    public function next(string $type): string
    {
        $prefix = config("sales.document_prefixes.{$type}", strtoupper($type));

        $table = match ($type) {
            'customer' => 'customers',
            'sales_order' => 'sales_orders',
            'sales_invoice' => 'sales_invoices',
            'customer_receipt' => 'customer_receipts',
            'sales_return' => 'sales_returns',
            default => throw new SalesException("Unknown document type: {$type}"),
        };

        $column = match ($type) {
            'customer' => 'code',
            'sales_order' => 'order_number',
            'sales_invoice' => 'invoice_number',
            'customer_receipt' => 'receipt_number',
            'sales_return' => 'return_number',
            default => 'code',
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