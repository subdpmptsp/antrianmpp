# Deployment produksi

1. Pastikan backup database terbaru sudah diuji restore.
2. Pastikan workflow GitHub Actions `CI` pada commit yang akan dipasang berstatus hijau.
3. Salin `.env.production.example` menjadi `.env`, ganti seluruh nilai `CHANGE_ME`, buat `APP_KEY`, lalu verifikasi `APP_ENV=production`, `APP_DEBUG=false`, `LOG_STACK=daily`, dan `LOG_LEVEL=info`.
4. Jalankan `powershell -ExecutionPolicy Bypass -File scripts/deploy-production.ps1` dari server aplikasi.
5. Script menjalankan `php artisan app:production-audit` sebelum maintenance. Deployment otomatis dihentikan jika konfigurasi wajib tidak aman.
6. Setelah migrasi, script menjalankan `php artisan app:data-integrity-audit`. Deployment berhenti bila masih ada operator tanpa loket, layanan/loket tanpa pasangan, relasi pivot lama, status antrean ilegal, atau timestamp lifecycle yang tidak lengkap.
7. Setelah aplikasi kembali aktif, script mengukur tujuh endpoint utama. Default gerbang adalah rata-rata maksimal 250 ms dan maksimal 15 query per request. Gunakan `-SkipBenchmark` hanya untuk pemulihan darurat dan catat alasannya.

Script juga menghentikan deployment bila PHP OPcache belum dimuat/aktif atau masih ada petugas yang belum merotasi password sejak batas audit.

Untuk PHP Laragon/Windows, aktifkan dan sesuaikan bagian berikut pada `php.ini`, lalu restart Apache/Nginx:

```ini
zend_extension=opcache
opcache.enable=1
opcache.enable_cli=0
opcache.memory_consumption=128
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
```

Verifikasi menggunakan `php -m` dan pastikan **Zend OPcache** tercantum.

Benchmark juga dapat dijalankan terpisah:

```powershell
php artisan app:benchmark-endpoints --runs=5 --max-average-ms=250 --max-query-count=15
php artisan app:benchmark-endpoints --runs=5 --json
```

Sebelum produksi, admin harus mereset password setiap petugas dari menu **Manajemen Pengguna**. Verifikasi tanpa menampilkan password:

```powershell
php artisan app:operator-password-audit
```

Kolom **Rotasi Password** menunjukkan waktu hash terakhir diperbarui. Jangan mencatat password sementara pada repository, log, atau spreadsheet.
7. Script selalu mengaktifkan aplikasi kembali dan membersihkan cache jika salah satu langkah setelah maintenance gagal.
8. Verifikasi `/up`, login admin, loket panggilan, cetak tiket, serta TV setiap zona.

Jika server memakai HTTPS, wajib isi `APP_URL=https://...` dan `SESSION_SECURE_COOKIE=true`. Peringatan otorisasi perangkat boleh ditunda hanya sampai token kiosk/TV selesai dipasang secara fisik.

## Laravel Scheduler pada Windows/Laragon

Scheduler wajib aktif agar absensi yang belum logout ditutup otomatis dan cache audio TTS lama dibersihkan. Jalankan sekali dari PowerShell **Run as Administrator**:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/install-windows-scheduler.ps1
```

Script memasang task `AntrianMPP-LaravelScheduler` sebagai `SYSTEM` setiap satu menit. Verifikasi dengan:

```powershell
Get-ScheduledTask -TaskName AntrianMPP-LaravelScheduler
php artisan schedule:list
```

Jadwal aplikasi saat ini:

- `attendance:reset-daily` setiap pukul 00:00 WIB.
- `audio:cleanup-generated --days=7` setiap pukul 02:00 WIB.

File konfigurasi audio dan audio unggahan petugas tidak termasuk dalam pembersihan otomatis.

Untuk rollback kode, kembalikan release sebelumnya lalu jalankan script dengan `-SkipMigrations`. Jangan menjalankan `migrate:rollback` tanpa meninjau dampak datanya.
