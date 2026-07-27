<?php

return [

    'super_admin' => [
        'email' => env('SUPER_ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('SUPER_ADMIN_PASSWORD', 'ChangeMe123!'),
    ],

    'customer_number_prefix' => env('CUSTOMER_NUMBER_PREFIX', 'CUS'),

    'loan_number_prefix' => env('LOAN_NUMBER_PREFIX', 'LN'),

    'default_interest_rate' => (float) env('LOAN_DEFAULT_INTEREST_RATE', 40),

    'default_duration_months' => (int) env('LOAN_DEFAULT_DURATION_MONTHS', 1),

    'default_frequency' => env('LOAN_DEFAULT_FREQUENCY', 'monthly'),

    'default_interest_type' => env('LOAN_DEFAULT_INTEREST_TYPE', 'flat'),

    'default_penalty_type' => env('LOAN_DEFAULT_PENALTY_TYPE', 'daily_percent'),

    'default_penalty_value' => (float) env('LOAN_DEFAULT_PENALTY_VALUE', 0),

    'grace_period_days' => (int) env('LOAN_GRACE_PERIOD_DAYS', 0),

    'currency_code' => env('LMS_CURRENCY_CODE', 'ZMW'),

    'currency_symbol' => env('LMS_CURRENCY_SYMBOL', 'K'),

    'payment_number_prefix' => env('PAYMENT_NUMBER_PREFIX', 'PAY'),

];
