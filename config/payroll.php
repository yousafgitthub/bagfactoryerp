<?php

return [
    'run_statuses' => [
        'draft' => 'Draft',
        'reviewed' => 'Reviewed',
        'approved' => 'Approved',
        'partially_paid' => 'Partially Paid',
        'paid' => 'Paid',
        'cancelled' => 'Cancelled',
    ],

    'record_statuses' => [
        'draft' => 'Draft',
        'reviewed' => 'Reviewed',
        'approved' => 'Approved',
        'partially_paid' => 'Partially Paid',
        'paid' => 'Paid',
        'voided' => 'Voided',
    ],

    'advance_statuses' => [
        'open' => 'Open',
        'settled' => 'Settled',
        'cancelled' => 'Cancelled',
    ],

    'loan_statuses' => [
        'active' => 'Active',
        'closed' => 'Closed',
        'cancelled' => 'Cancelled',
    ],

    'payment_methods' => [
        'cash' => 'Cash',
        'bank' => 'Bank transfer',
        'cheque' => 'Cheque',
    ],

    'line_types' => [
        'basic' => 'Basic pay',
        'overtime' => 'Overtime',
        'bonus' => 'Bonus',
        'allowance' => 'Allowance',
        'absence_deduction' => 'Absence deduction',
        'advance_deduction' => 'Advance recovery',
        'loan_deduction' => 'Loan installment',
        'other_deduction' => 'Other deduction',
    ],

    /*
    | Monthly salary absence divisor (configurable per factory policy).
    */
    'monthly_absence_divisor' => (float) env('PAYROLL_MONTHLY_ABSENCE_DIVISOR', 30),

    /*
    | Attendance status weights for payroll (0 = unpaid, 1 = full day, 0.5 = half).
    */
    'attendance_pay_weights' => [
        'present' => 1.0,
        'half_day' => 0.5,
        'paid_leave' => 1.0,
        'unpaid_leave' => 0.0,
        'absent' => 0.0,
        'holiday' => 1.0,
    ],

    /*
    | For monthly employees: statuses that count toward "present" days (no absence deduction).
    */
    'monthly_present_statuses' => ['present', 'half_day', 'paid_leave', 'holiday'],

    /*
    | Half-day fraction counted as absence on monthly payroll (deduct 0.5 day equivalent).
    */
    'monthly_half_day_deduction_fraction' => 0.5,

    'default_advance_payroll_deduction' => null,
];