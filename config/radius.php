<?php

declare(strict_types=1);

return [
    'timeout' => (int) env('RADIUS_API_TIMEOUT', 10),

    'platforms' => [
        'freeradius' => [
            'url' => env('FREERADIUS_API_URL'),
            'token' => env('FREERADIUS_API_TOKEN'),
        ],
        'mikrotik' => [
            'url' => env('MIKROTIK_RADIUS_API_URL'),
            'token' => env('MIKROTIK_RADIUS_API_TOKEN'),
        ],
        'cisco' => [
            'url' => env('CISCO_RADIUS_API_URL'),
            'token' => env('CISCO_RADIUS_API_TOKEN'),
        ],
    ],
];
