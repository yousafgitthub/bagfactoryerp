<?php

return [
    'salary_types' => [
        'monthly' => 'Monthly',
        'daily' => 'Daily',
    ],

    'employee_statuses' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'attendance_statuses' => [
        'present' => 'Present',
        'absent' => 'Absent',
        'half_day' => 'Half Day',
        'paid_leave' => 'Paid Leave',
        'unpaid_leave' => 'Unpaid Leave',
        'holiday' => 'Holiday',
    ],

    'overtime_approval_statuses' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
    ],

    'production_assignment_types' => [
        'department' => 'Department',
        'dana_machine' => 'Dana machine',
        'bag_machine' => 'Shopping bag machine',
        'dana_batch' => 'Dana production batch',
        'bag_batch' => 'Bag production batch',
        'bag_order' => 'Bag production order',
    ],

    'default_working_hours' => 8.0,
    'half_day_working_hours' => 4.0,
    'standard_day_hours_for_overtime_rate' => 8,

    'shift_based_attendance' => false,
];