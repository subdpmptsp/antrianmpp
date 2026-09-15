<?php

return [
    'panrb' => [
        'enabled' => env('PANRB_API_ENABLED', false),
        'environment' => env('PANRB_API_ENVIRONMENT', 'sandbox'),
        'base_url' => env('PANRB_API_BASE_URL'),
        'endpoints' => [
            'ping' => env('PANRB_API_PING_ENDPOINT', '/sync/ping'),
            'master_data' => env('PANRB_API_MASTER_DATA_ENDPOINT', '/sync/master-data'),
            'submit' => env('PANRB_API_SUBMIT_ENDPOINT', '/sync/submit'),
        ],
        'auth_header' => env('PANRB_API_AUTH_HEADER', 'X-API-Key'),
        'api_key' => env('PANRB_API_KEY'),
        'timeout_seconds' => (int) env('PANRB_API_TIMEOUT', 10),
        'retry_times' => (int) env('PANRB_API_RETRY_TIMES', 2),
        'failure_mode' => env('PANRB_API_FAILURE_MODE', 'hold'),
    ],
];
