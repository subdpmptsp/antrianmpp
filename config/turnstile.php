<?php

return [
    'enabled' => env(
        'TURNSTILE_ENABLED',
        filled(env('TURNSTILE_SITE_KEY')) && filled(env('TURNSTILE_SECRET_KEY')),
    ),

    'turnstile_site_key' => env('TURNSTILE_SITE_KEY'),
    'turnstile_secret_key' => env('TURNSTILE_SECRET_KEY'),
    'expected_hostname' => env('TURNSTILE_EXPECTED_HOSTNAME'),
    'booking_action' => 'online_queue_booking',

    'error_messages' => [
        'turnstile_check_message' => 'Verifikasi keamanan gagal. Silakan muat ulang halaman dan coba kembali.',
    ],
];
