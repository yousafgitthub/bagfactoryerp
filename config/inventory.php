<?php

return [
  /*
  | Allow posting issues/transfers that would drive quantity below zero.
  | When false, only users with inventory.manage may override (still blocked by default).
  */
    'allow_negative_stock' => (bool) env('INVENTORY_ALLOW_NEGATIVE_STOCK', false),

    'transaction_types' => [
        'opening_stock' => 'Opening stock',
        'purchase_receipt' => 'Purchase / goods receipt',
        'issue_to_production' => 'Issue to production',
        'return_unused' => 'Return unused material',
        'adjustment' => 'Stock adjustment',
        'warehouse_transfer' => 'Warehouse transfer',
        'waste_receipt' => 'Waste stock receipt',
        'production_receipt' => 'Production output receipt',
        'reversal' => 'Reversal',
    ],

    'inventory_buckets' => [
        'raw' => 'Raw materials',
        'dana' => 'Dana',
        'finished_bag' => 'Finished bags',
        'rejected_bag' => 'Rejected bags (non-saleable)',
        'other' => 'Other',
    ],
];