<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default MDM Engine
    |--------------------------------------------------------------------------
    |
    | Global default engine. Per-device override via devices.mdm_engine.
    | Valid values: nano, custom
    |
    */
    'engine' => env('MDM_ENGINE', 'nano'),

    'nano' => [
        'base_url' => env('NANO_MDM_BASE_URL', 'http://127.0.0.1:9000'),
        'api_key' => env('NANO_MDM_API_KEY', ''),
        'timeout' => (int) env('NANO_MDM_TIMEOUT', 30),
    ],

    'custom' => [
        'base_url' => env('CUSTOM_MDM_BASE_URL', 'http://127.0.0.1:9001'),
        'api_key' => env('CUSTOM_MDM_API_KEY', ''),
        'timeout' => (int) env('CUSTOM_MDM_TIMEOUT', 30),
    ],

    'command_timeout_seconds' => (int) env('MDM_COMMAND_TIMEOUT', 300),
];
