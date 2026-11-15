<?php

namespace App\Services\Purchases;

use Illuminate\Support\Facades\DB;

class PurchaseDocumentNumberGenerator
{
    public function next(string $type): string
    {
        $prefix = config("purchases.document_prefixes.{$type}", strtoupper($type));

        $table = match ($type) {
            'supplier' => 'suppliers',
            'purchase_order' => 'purchase_orders',
            'goods_receipt' => 'goods_receipts',
            'purchase_invoice' => 'purchase_invoices',
            'supplier_payment' => 'supplier_payments',
            'purchase_return' => 'purchase_returns',
            default => throw new PurchaseException("Unknown document type: {$type}"),
        };

        $column = match ($type) {
            'supplier' => 'code',
            'purchase_order' => 'order_number',
            'goods_receipt' => 'receipt_number',
            'purchase_invoice' => 'invoice_number',
            'supplier_payment' => 'payment_number',
            'purchase_return' => 'return_number',
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