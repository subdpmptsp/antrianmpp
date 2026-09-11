<?php

return [
    // Tetap false sampai kontrak, alamat, autentikasi, dan izin resmi API tersedia.
    'enabled' => env('DUKCAPIL_API_ENABLED', false),
    'driver' => env('DUKCAPIL_API_DRIVER', 'disabled'),
    'endpoint' => env('DUKCAPIL_API_ENDPOINT'),
    'health_endpoint' => env('DUKCAPIL_API_HEALTH_ENDPOINT'),
    'method' => env('DUKCAPIL_API_METHOD', 'POST'),
    'auth_type' => env('DUKCAPIL_API_AUTH_TYPE', 'bearer'),
    'client_id' => env('DUKCAPIL_API_CLIENT_ID'),
    'client_secret' => env('DUKCAPIL_API_CLIENT_SECRET'),
    'token' => env('DUKCAPIL_API_TOKEN'),
    'timeout_seconds' => (int) env('DUKCAPIL_API_TIMEOUT', 5),
    // Saat API resmi tidak tersedia, reservasi ditandai untuk pemeriksaan,
    // bukan menerima identitas sebagai sudah terverifikasi.
    'failure_mode' => env('DUKCAPIL_API_FAILURE_MODE', 'manual_review'),
];
