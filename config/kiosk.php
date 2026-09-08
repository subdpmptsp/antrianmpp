<?php

return [
    /*
     * Urutan halaman awal kiosk adalah keputusan operasional, bukan ranking
     * jumlah antrean. Nilai "bpjs" adalah satu kartu virtual yang membuka
     * pilihan BPJS Kesehatan dan BPJS Ketenagakerjaan.
     */
    'institution_order' => [
        'Dinas Kependudukan dan Pencatatan Sipil',
        'UPTSP',
        'Kepolisian Resor Kota Besar (Polrestabes)',
        'Klinik Investasi',
        'bpjs',
        'Direktorat Jenderal Pajak',
        'Dinas Lingkungan Hidup',
        'Dinas Perhubungan',
        'Badan Pendapatan Daerah',
        'Dinas Perumahan Rakyat dan Kawasan Permukiman serta Pertanahan (DPRKPP)',
        'Pengadilan Agama',
        'Bagian Pengadaan Barang/Jasa dan Administrasi Pembangunan (BPBJAP)',
        'Badan Narkotika Surabaya',
        'PT Pos Indonesia',
        'Perumda Air Minum Surya Sembada',
        'Bursa Efek dan BNI Sekuritas',
        'Kantor Pertanahan Kota Surabaya',
        'Kejaksaan Negeri Tanjung Perak',
        'Kejaksaan Negeri Surabaya',
        'Pengadilan Tata Usaha Negeri Surabaya',
        'Pengadilan Negeri Surabaya',
    ],

    'bpjs_institutions' => [
        'BPJS Kesehatan',
        'BPJS Ketenagakerjaan',
    ],

    /*
     * Layanan tertentu beroperasi sebagai satu unit: bila salah satu loket
     * mengajukan tutup dan disetujui, pengambilan nomor seluruh layanan ikut
     * dihentikan. Ini sengaja berbeda dari layanan bergilir seperti
     * Dispendukcapil, yang tetap menerima nomor selama masih ada loket lain.
     */
    'close_entire_service_when_any_counter_closes' => [
        [
            'instansi' => 'Kepolisian Resor Kota Besar (Polrestabes)',
            'service_prefix' => '2B', // Layanan ETLE
        ],
    ],
];
