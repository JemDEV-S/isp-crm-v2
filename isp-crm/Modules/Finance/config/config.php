<?php

return [
    'name' => 'Finance',

    'billing' => [
        'grace_period_days' => env('BILLING_GRACE_PERIOD', 10),
        'tax_rate' => env('BILLING_TAX_RATE', 0.00),
        'tax_enabled' => env('BILLING_TAX_ENABLED', false),
        'tax_name' => env('BILLING_TAX_NAME', 'IGV'),
        'external_tax_integration' => env('BILLING_EXTERNAL_TAX', false),
        'invoice_prefix' => env('BILLING_INVOICE_PREFIX', 'FAC'),
        'billing_policy' => env('BILLING_POLICY', 'advance'),
        'short_month_strategy' => env('BILLING_SHORT_MONTH', 'last_day'),
        'max_retry_attempts' => env('BILLING_MAX_RETRIES', 3),
        'notification_channels' => ['email'],
    ],

    'dunning' => [
        'enabled' => env('DUNNING_ENABLED', true),
        'default_policy' => 'standard_residential',
        'promise_max_days' => env('DUNNING_PROMISE_MAX_DAYS', 7),
        'promise_max_extensions' => env('DUNNING_PROMISE_MAX_EXTENSIONS', 1),
        'suspension_requires_approval' => env('DUNNING_SUSPENSION_APPROVAL', false),
        'auto_resume_after_broken_promise' => true,
        'exclude_corporate_from_auto_suspension' => false,
    ],

    'payments' => [
        'default_currency' => env('PAYMENT_CURRENCY', 'PEN'),
        'auto_allocate' => env('PAYMENT_AUTO_ALLOCATE', true),
        'allocation_strategy' => 'oldest_first',
        'excess_to_wallet' => env('PAYMENT_EXCESS_TO_WALLET', true),
        'auto_reconnect' => env('PAYMENT_AUTO_RECONNECT', true),
        'reconnect_delay_seconds' => env('PAYMENT_RECONNECT_DELAY', 0),
    ],

    'webhooks' => [
        'gateways' => [
            'mercadopago' => [
                'secret' => env('WEBHOOK_MERCADOPAGO_SECRET'),
                'ip_whitelist' => env('WEBHOOK_MERCADOPAGO_IPS', ''),
            ],
            'niubiz' => [
                'secret' => env('WEBHOOK_NIUBIZ_SECRET'),
                'ip_whitelist' => env('WEBHOOK_NIUBIZ_IPS', ''),
            ],
        ],
        'replay_protection_minutes' => 5,
    ],
];
