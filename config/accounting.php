<?php

return [
    'enabled' => (bool) env('ACCOUNTING_ENABLED', true),

    'auto_post' => (bool) env('ACCOUNTING_AUTO_POST', true),

    'document_prefixes' => [
        'journal' => 'JE',
        'treasury_movement' => 'TM',
        'expense' => 'EXP',
        'company_loan' => 'CLN',
    ],

    'account_codes' => [
        'cash' => '1000',
        'bank' => '1010',
        'accounts_receivable' => '1100',
        'inventory' => '1200',
        'grni' => '1300',
        'loans_receivable' => '1400',
        'accounts_payable' => '2000',
        'salaries_payable' => '2100',
        'accrued_expenses' => '2200',
        'owner_equity' => '3000',
        'loans_payable' => '3100',
        'sales_revenue' => '4000',
        'cogs' => '5000',
        'payroll_expense' => '5100',
        'interest_expense' => '5200',
        'factory_expense' => '6000',
    ],

    'treasury_account_codes' => [
        'default_cash' => 'CASH-01',
        'default_bank' => 'BANK-01',
    ],

    'payment_methods' => [
        'cash' => 'Cash',
        'bank' => 'Bank transfer',
        'cheque' => 'Cheque',
    ],

    'expense_payment_statuses' => [
        'paid' => 'Paid',
        'unpaid' => 'Unpaid bill',
    ],

    'treasury_movement_types' => [
        'receipt' => 'Receipt',
        'payment' => 'Payment',
        'deposit' => 'Deposit',
        'withdrawal' => 'Withdrawal',
        'transfer' => 'Transfer',
    ],

    'loan_directions' => [
        'received' => 'Loan received',
        'given' => 'Loan given',
    ],

    'loan_statuses' => [
        'active' => 'Active',
        'closed' => 'Closed',
        'defaulted' => 'Defaulted',
    ],

    'gl_account_types' => [
        'asset' => 'Asset',
        'liability' => 'Liability',
        'equity' => 'Equity',
        'revenue' => 'Revenue',
        'expense' => 'Expense',
    ],
];