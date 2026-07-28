<?php

declare(strict_types=1);

return [
    'timeout' => (int) env('VOIP_API_TIMEOUT', 10),
    'fraud' => [
        'single_call_cost' => (float) env('VOIP_FRAUD_SINGLE_CALL_COST', 50),
        'call_duration_seconds' => (int) env('VOIP_FRAUD_CALL_DURATION', 14400),
        'high_risk_prefixes' => array_values(array_filter(explode(',', (string) env('VOIP_HIGH_RISK_PREFIXES', '')))),
    ],
    'platforms' => [
        'asterisk' => ['url' => env('ASTERISK_API_URL'), 'token' => env('ASTERISK_API_TOKEN')],
        'freepbx' => ['url' => env('FREEPBX_API_URL'), 'token' => env('FREEPBX_API_TOKEN')],
        'fusionpbx' => ['url' => env('FUSIONPBX_API_URL'), 'token' => env('FUSIONPBX_API_TOKEN')],
        '3cx' => ['url' => env('THREECX_API_URL'), 'token' => env('THREECX_API_TOKEN')],
    ],
];
