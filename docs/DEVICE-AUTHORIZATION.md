# Otorisasi mesin antrian dan TV layanan

Fitur ini membatasi mesin antrian dengan satu token dan TV dengan token berbeda untuk setiap zona. Token mentah tidak disimpan di session; aplikasi hanya menyimpan hash SHA-256 dan nomor zona yang telah disahkan.

## Aktivasi

1. Buat token acak yang panjang untuk kiosk dan setiap TV.
2. Isi `KIOSK_DEVICE_TOKEN` dan `TV_ZONE_1_TOKEN` sampai `TV_ZONE_5_TOKEN` di `.env`.
3. Jalankan `php artisan config:clear`.
4. Uji setiap perangkat memakai URL awal, misalnya `/tv1?device_token=TOKEN_ZONA_1` dan `/kiosk/cetak-antrian?device_token=TOKEN_KIOSK`.
5. Pastikan TV Zona 1 tidak dapat membuka API zona lain.
6. Setelah semua perangkat berhasil diuji, ubah `DEVICE_AUTH_ENABLED=true`, lalu jalankan `php artisan config:cache`.

Header `X-Device-Token` dapat digunakan sebagai pengganti parameter URL. Setelah token benar, otorisasi dipertahankan dalam session browser. Menghapus cookie/session akan meminta token kembali.

Jangan mengirim token melalui chat, menyimpannya di source control, atau memakai token yang sama untuk beberapa zona.
