<?php

return [
    /*
    | Stock is recognized when a goods receipt (GRN) is posted — not on purchase invoice.
    | Invoices record supplier liability only; inventory uses weighted-average cost from GRN unit cost.
    */
    'stock_recognition' => 'goods_receipt',

    'purchase_order_statuses' => [
        'draft' => 'Draft',
        'confirmed' => 'Confirmed',
        'partially_received' => 'Partially received',
        'received' => 'Fully received',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ],

    'goods_receipt_statuses' => [
        'draft' => 'Draft',
        'posted' => 'Posted',
        'cancelled' => 'Cancelled',
    ],

    'invoice_statuses' => [
        'draft' => 'Draft',
        'posted' => 'Posted',
        'partially_paid' => 'Partially paid',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ],

    'return_statuses' => [
        'draft' => 'Draft',
        'posted' => 'Posted',
        'cancelled' => 'Cancelled',
    ],

    'payment_methods' => [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ],

    'document_prefixes' => [
        'supplier' => 'SUP',
        'purchase_order' => 'PO',
        'goods_receipt' => 'GRN',
        'purchase_invoice' => 'PI',
        'supplier_payment' => 'SP',
        'purchase_return' => 'PR',
    ],
];