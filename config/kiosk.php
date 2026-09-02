<?php

return [
    // Jumlah instansi teratas berdasarkan total tiket bulan berjalan.
    'popular_institution_count' => (int) env('KIOSK_POPULAR_INSTITUTION_COUNT', 4),

    // Opsional: jika diisi, hanya instansi yang melewati angka ini yang masuk populer.
    'popular_minimum_total' => env('KIOSK_POPULAR_MINIMUM_TOTAL') !== null
        ? (int) env('KIOSK_POPULAR_MINIMUM_TOTAL')
        : null,
];
