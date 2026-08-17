<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Device authorization
    |--------------------------------------------------------------------------
    |
    | Keep this disabled until every kiosk and TV has received its token.
    | Once enabled, public device screens and their zone APIs require a token.
    |
    */
    'auth_enabled' => env('DEVICE_AUTH_ENABLED', false),

    'kiosk' => [
        'token' => env('KIOSK_DEVICE_TOKEN'),
    ],

    'tv' => [
        'tokens' => [
            1 => env('TV_ZONE_1_TOKEN'),
            2 => env('TV_ZONE_2_TOKEN'),
            3 => env('TV_ZONE_3_TOKEN'),
            4 => env('TV_ZONE_4_TOKEN'),
            5 => env('TV_ZONE_5_TOKEN'),
        ],
    ],
];
