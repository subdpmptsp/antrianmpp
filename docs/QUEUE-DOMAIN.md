# Kontrak domain antrian

Dokumen ini menetapkan sumber kebenaran yang dipakai kode operasional dan pengujian.

## Status resmi

Urutan normal adalah `waiting` → `called` → `serving` → `finished`. Status `waiting` atau `called` dapat berubah menjadi `canceled`. Nilai lama `completed` dan `cancelled` hanya dikenali oleh migrasi normalisasi dan tidak boleh digunakan oleh kode aplikasi.

Timestamp wajib:

- `called` memiliki `called_at`.
- `serving` memiliki `called_at` dan `served_at`.
- `finished` memiliki `called_at`, `served_at`, dan `finished_at`.
- `canceled` memiliki `canceled_at`.

## Relasi loket dan layanan

Sumber kebenaran adalah `counters.service_id`:

- Satu loket menangani tepat satu layanan.
- Satu layanan boleh ditangani beberapa loket.
- Tabel pivot `counter_service` dan kolom lama `services.counter_id` tidak digunakan oleh alur panggil, dashboard loket, atau TV.
- Loket aktif tanpa `service_id` ditolak oleh `app:data-integrity-audit` sebelum deployment selesai.

Perubahan relasi wajib dilakukan melalui Manajemen Loket dan dilanjutkan dengan pengujian panggil antrean. Jangan mengisi tabel pivot lama.

## Gerbang deployment

Jalankan setelah migrasi:

```powershell
php artisan app:data-integrity-audit
```

Exit code bukan nol berarti deployment harus dihentikan sampai data diperbaiki.
