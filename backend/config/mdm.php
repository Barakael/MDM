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

    /*
    |--------------------------------------------------------------------------
    | NanoMDM
    |--------------------------------------------------------------------------
    |
    | base_url     — Laravel → NanoMDM API (enqueue, pushcert, version)
    | public_url   — Device-facing NanoMDM (ServerURL / CheckInURL in profiles)
    | scep_url     — Device-facing SCEP endpoint
    | webhook_url  — NanoMDM → Laravel (also used by start-nanomdm.sh)
    |
    | Flip public_* / APP_URL / VITE_API_URL when moving from LAN IP to HTTPS.
    |
    */
    'nano' => [
        'base_url' => env('NANO_MDM_BASE_URL', 'http://127.0.0.1:9000'),
        'api_key' => env('NANO_MDM_API_KEY', 'nanomdm'),
        'timeout' => (int) env('NANO_MDM_TIMEOUT', 30),
        'public_url' => env('NANO_MDM_PUBLIC_URL', env('NANO_MDM_BASE_URL', 'http://127.0.0.1:9000')),
        'scep_url' => env('NANO_SCEP_URL', 'http://127.0.0.1:8080/scep'),
        'scep_challenge' => env('NANO_SCEP_CHALLENGE', 'nanomdm'),
        'topic' => env('NANO_MDM_TOPIC', ''),
        'webhook_url' => env('NANO_MDM_WEBHOOK_URL', env('NANOMDM_WEBHOOK_URL', '')),
        'webhook_secret' => env('NANO_MDM_WEBHOOK_SECRET', ''),
        'default_organization_id' => env('NANO_MDM_DEFAULT_ORGANIZATION_ID')
            ? (int) env('NANO_MDM_DEFAULT_ORGANIZATION_ID')
            : null,
    ],

    'custom' => [
        'base_url' => env('CUSTOM_MDM_BASE_URL', 'http://127.0.0.1:9001'),
        'api_key' => env('CUSTOM_MDM_API_KEY', ''),
        'timeout' => (int) env('CUSTOM_MDM_TIMEOUT', 30),
    ],

    'command_timeout_seconds' => (int) env('MDM_COMMAND_TIMEOUT', 300),
];
