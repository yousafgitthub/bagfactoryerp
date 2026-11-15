<?php

return [
    'default_max_process_loss_percent' => (float) env('BAG_MAX_PROCESS_LOSS_PERCENT', 5),
    'mass_balance_tolerance_kg' => (float) env('BAG_MASS_BALANCE_TOLERANCE_KG', 0.05),
    'weight_tolerance_percent' => (float) env('BAG_WEIGHT_TOLERANCE_PERCENT', 15),

    'accounting' => [
        'enabled' => (bool) env('BAG_ACCOUNTING_INTEGRATION', false),
    ],

    'order_statuses' => [
        'draft' => 'Draft',
        'released' => 'Released',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'order_transitions' => [
        'draft' => ['release' => 'released', 'cancel' => 'cancelled'],
        'released' => ['start' => 'in_progress', 'return_to_draft' => 'draft', 'cancel' => 'cancelled'],
        'in_progress' => ['complete' => 'completed', 'cancel' => 'cancelled'],
    ],

    'batch_statuses' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'approved' => 'Approved',
        'in_progress' => 'In progress',
        'completed' => 'Completed',
        'posted' => 'Posted',
        'cancelled' => 'Cancelled',
        'reversed' => 'Reversed',
    ],

    'batch_transitions' => [
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

    'consumption_types' => [
        'dana' => 'Dana',
        'material' => 'Other material',
        'color' => 'Color / masterbatch',
    ],

    'waste_types' => [
        'reusable' => 'Reusable waste',
        'scrap' => 'Scrap waste',
        'process_loss' => 'Process loss',
    ],

    'bag_types' => [
        't_shirt' => 'T-shirt bag',
        'loop_handle' => 'Loop handle',
        'die_cut' => 'Die cut',
        'garbage' => 'Garbage / liner',
        'other' => 'Other',
    ],
];