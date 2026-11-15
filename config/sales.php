<?php

return [
    /*
    | Finished-goods stock is deducted when a sales invoice is posted (WAC COGS).
    | Sales returns restore saleable or rejected stock only after posted inspection/disposition.
    */
    'costing_method' => 'weighted_average',

    'allow_oversell' => (bool) env('SALES_ALLOW_OVERSELL', false),

    'sale_types' => [
        'cash' => 'Cash sale',
        'credit' => 'Credit sale',
    ],

    'sales_order_statuses' => [
        'draft' => 'Draft',
        'confirmed' => 'Confirmed',
        'partially_invoiced' => 'Partially invoiced',
        'invoiced' => 'Fully invoiced',
        'closed' => 'Closed',
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

    'return_conditions' => [
        'saleable' => 'Saleable',
        'damaged' => 'Damaged / rejected',
    ],

    'payment_methods' => [
        'cash' => 'Cash',
        'bank_transfer' => 'Bank transfer',
        'cheque' => 'Cheque',
        'other' => 'Other',
    ],

    'document_prefixes' => [
        'customer' => 'CUS',
        'sales_order' => 'SO',
        'sales_invoice' => 'SI',
        'customer_receipt' => 'CR',
        'sales_return' => 'SR',
    ],

    'company' => [
        'name' => env('SALES_COMPANY_NAME', 'Bag Factory'),
        'address' => env('SALES_COMPANY_ADDRESS', ''),
        'phone' => env('SALES_COMPANY_PHONE', ''),
    ],
];