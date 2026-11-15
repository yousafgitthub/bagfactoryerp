<?php

return [
    'default_max_process_loss_percent' => (float) env('DANA_MAX_PROCESS_LOSS_PERCENT', 5),
    'mass_balance_tolerance_kg' => (float) env('DANA_MASS_BALANCE_TOLERANCE_KG', 0.01),

    'accounting' => [
        'enabled' => (bool) env('DANA_ACCOUNTING_INTEGRATION', false),
    ],

    'statuses' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'approved' => 'Approved',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'posted' => 'Posted',
        'cancelled' => 'Cancelled',
        'reversed' => 'Reversed',
    ],

    /*
    | Workflow action => target status
    */
    'transitions' => [
        'draft' => ['submit' => 'submitted', 'cancel' => 'cancelled'],
        'submitted' => [
            'return_to_draft' => 'draft',
            'approve' => 'approved',
            'cancel' => 'cancelled',
        ],
        'approved' => [
            'start' => 'in_progress',
            'cancel' => 'cancelled',
        ],
        'in_progress' => [
            'complete' => 'completed',
            'cancel' => 'cancelled',
        ],
        'completed' => [
            'reopen' => 'in_progress',
        ],
    ],

    'input_types' => [
        'raw' => 'Raw material',
        'color' => 'Color / additive',
    ],

    'waste_types' => [
        'reusable' => 'Reusable waste',
        'scrap' => 'Scrap waste',
        'process_loss' => 'Process loss',
    ],
];