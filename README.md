## Persiapan Project Antrian

1. Local Server Laragon/Xampp 
2. Composer
3. Git
4. Node.js
5. php version >= 8.2

## Setup Project Antrian

Perhatikan untuk menjalankan atau mensetup project ini.

1. Buat database terlebih dahulu
2. Konfigurasikan file .env dengan database yang telah dibuat
3. Import Database dengan file antrian.sql pada projek
7. Pada Windows, jalankan **Start All** di Laragon agar MySQL dan Apache/Nginx aktif. Gunakan URL virtual host Laragon untuk pengujian normal dan beberapa perangkat.
8. `php artisan serve` dan http://127.0.0.1:8000 dapat dipakai untuk pengembangan cepat satu pengguna, tetapi server bawaan PHP di Windows hanya satu proses sehingga bukan acuan performa multi-perangkat.
9. Jika akun admin awal belum tersedia, isi `INITIAL_ADMIN_PASSWORD` di `.env` dengan minimal 12 karakter, lalu jalankan `php artisan db:seed --class=AdminUserSeeder`.
10. Login menggunakan username `admin`, segera ganti password melalui Manajemen Pengguna, lalu kosongkan kembali `INITIAL_ADMIN_PASSWORD`.

Aplikasi siap di gunakan....

## Pemeriksaan performa

Ukur seluruh menu admin menggunakan data saat ini tanpa meminta atau menampilkan password:

```powershell
php artisan app:benchmark-admin --runs=5 --max-average-ms=500 --max-query-count=40
```


