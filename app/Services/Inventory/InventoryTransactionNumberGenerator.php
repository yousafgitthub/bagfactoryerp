<?php

namespace App\Services\Inventory;

use App\Models\InventoryTransaction;
use Illuminate\Support\Str;

class InventoryTransactionNumberGenerator
{
    public function next(string $type): string
    {
        $prefix = match ($type) {
            'opening_stock' => 'OS',
            'purchase_receipt' => 'GR',
            'purchase_return' => 'PRT',
            'issue_to_production' => 'IS',
            'return_unused' => 'RT',
            'adjustment' => 'ADJ',
            'warehouse_transfer' => 'TR',
            'waste_receipt' => 'WS',
            'production_receipt' => 'PO',
            'reversal' => 'REV',
            default => 'INV',
        };

        $ym = now()->format('ym');

        do {
            $candidate = sprintf('%s-%s-%s', $prefix, $ym, Str::upper(Str::random(6)));
        } while (InventoryTransaction::query()->where('transaction_number', $candidate)->exists());

        return $candidate;
    }
}