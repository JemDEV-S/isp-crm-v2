<?php

return [
    'name' => 'Network',

    /*
    |--------------------------------------------------------------------------
    | PPPoE Configuration
    |--------------------------------------------------------------------------
    */
    'pppoe_prefix' => env('NETWORK_PPPOE_PREFIX', 'noretel'),
    'pppoe_password_length' => env('NETWORK_PPPOE_PASSWORD_LENGTH', 12),

    /*
    |--------------------------------------------------------------------------
    | RouterOS API Configuration
    |--------------------------------------------------------------------------
    */
    'routeros' => [
        'default_port' => env('ROUTEROS_DEFAULT_PORT', 8728),
        'timeout' => env('ROUTEROS_TIMEOUT', 10),
        'blocked_list_name' => env('ROUTEROS_BLOCKED_LIST', 'MOROSOS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OLT Configuration
    |--------------------------------------------------------------------------
    */
    'olt' => [
        'snmp_timeout' => env('OLT_SNMP_TIMEOUT', 5),
        'snmp_retries' => env('OLT_SNMP_RETRIES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feasibility Check Configuration
    |--------------------------------------------------------------------------
    */
    'feasibility' => [
        'default_radius_meters' => env('NETWORK_FEASIBILITY_RADIUS', 500),
        'max_radius_meters' => env('NETWORK_FEASIBILITY_MAX_RADIUS', 2000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Device Monitoring Configuration
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'check_interval_minutes' => env('NETWORK_MONITOR_INTERVAL', 5),
        'offline_threshold_minutes' => env('NETWORK_OFFLINE_THRESHOLD', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Pool Configuration
    |--------------------------------------------------------------------------
    */
    'ip_pool' => [
        'low_threshold_percentage' => env('NETWORK_IP_LOW_THRESHOLD', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | NAP Box Configuration
    |--------------------------------------------------------------------------
    */
    'nap' => [
        'types' => [
            'splitter_1x4' => ['ports' => 4, 'label' => 'Splitter 1:4'],
            'splitter_1x8' => ['ports' => 8, 'label' => 'Splitter 1:8'],
            'splitter_1x16' => ['ports' => 16, 'label' => 'Splitter 1:16'],
            'splitter_1x32' => ['ports' => 32, 'label' => 'Splitter 1:32'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Node Types
    |--------------------------------------------------------------------------
    */
    'node_types' => [
        'tower' => 'Torre',
        'datacenter' => 'Datacenter',
        'pop' => 'POP (Punto de Presencia)',
        'cabinet' => 'Gabinete',
    ],
];
