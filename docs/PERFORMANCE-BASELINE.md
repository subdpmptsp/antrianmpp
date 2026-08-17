# Baseline Performa Endpoint Utama

Baseline diukur dengan cache framework aktif, database pengembangan berisi 344 antrian, dan lima pengulangan setelah warm-up.

| Endpoint | Rata-rata | P95 | Query maksimum | Target |
|---|---:|---:|---:|---|
| `/admin/login` | 28,03 ms | 29,53 ms | 2 | Lulus |
| `/queue-status` | 3,98 ms | 4,65 ms | 2 | Lulus |
| `/kiosk/cetak-antrian` | 3,20 ms | 4,46 ms | 4 | Lulus |
| `/tv` | 2,61 ms | 2,87 ms | 5 | Lulus |
| `/tv1` | 3,36 ms | 4,10 ms | 5 | Lulus |
| `/api/tv-display/queue-status` | 6,47 ms | 6,82 ms | 8 | Lulus |
| `/api/tv-display/latest-announcement` | 2,31 ms | 2,73 ms | 3 | Lulus |

Gerbang yang digunakan adalah rata-rata maksimal 250 ms dan maksimal 15 query per request.

```powershell
php artisan optimize
php artisan app:benchmark-endpoints --runs=5 --max-average-ms=250 --max-query-count=15
php artisan optimize:clear
```

Angka server produksi wajib direkam ulang setelah deployment karena perangkat keras, jaringan, dan beban riil dapat memberikan hasil berbeda. Script deployment menjalankan benchmark yang sama secara otomatis setelah aplikasi kembali aktif.
