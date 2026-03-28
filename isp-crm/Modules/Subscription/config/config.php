<?php

return [
    'name' => 'Subscription',

    'plan_change' => [
        'default_effective_mode' => env('PLAN_CHANGE_MODE', 'immediate'),
        'downgrade_mode' => env('PLAN_CHANGE_DOWNGRADE_MODE', 'next_cycle'),
        'upgrade_mode' => env('PLAN_CHANGE_UPGRADE_MODE', 'immediate'),
        'require_approval_for_downgrade' => env('PLAN_CHANGE_APPROVAL_DOWNGRADE', false),
        'require_approval_during_promotion' => env('PLAN_CHANGE_APPROVAL_PROMO', true),
        'minimum_stay_days' => env('PLAN_CHANGE_MIN_STAY', 0),
        'downgrade_credit_to' => env('PLAN_CHANGE_CREDIT_TO', 'wallet'),
        'max_pending_requests' => 1,
        'prorate_calculation' => 'daily',
    ],
];
