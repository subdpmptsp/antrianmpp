# Kehadiran Petugas

Halaman admin: `/admin/attendances`

## Aturan utama

- Hanya akun dengan peran `operator` dan status **Akun Aktif** yang dihitung.
- Login pertama petugas pada tanggal berjalan menjadi tanda hadir dan jam masuk. Login ulang tidak mengubah jam pertama.
- Setiap akun tetap ditampilkan sebagai hadir/belum hadir agar admin mengetahui petugas yang belum login.
- Satu instansi dianggap **terwakili** apabila minimal satu akun petugas instansi tersebut sudah login pada hari kerja itu.
- Akun yang belum terhubung ke instansi ditampilkan sebagai anomali master data dan tidak masuk persentase.
- Data harian tidak dihapus pada pukul 00.00. Dashboard cukup membaca tanggal berjalan sehingga riwayat tetap tersimpan.

## Hari kerja efektif

Pola hari kerja diatur pada menu **Instansi**:

- `5 hari`: Senin–Jumat.
- `6 hari`: Senin–Sabtu.

Hari yang tercatat pada menu **Kalender Hari Libur** selalu dikeluarkan dari penyebut persentase. Rumus rekap instansi:

```text
hari instansi terwakili / hari kerja efektif × 100%
```

Tanggal masa depan tidak dihitung. Bulan yang belum berjalan ditampilkan sebagai `–`, bukan `0%`.

## Kalender hari libur

Admin dapat menambah/mengubah tanggal secara manual atau mengimpor CSV. Format header yang diterima:

```csv
tanggal,nama,jenis,catatan
2026-08-17,Hari Kemerdekaan,national,
2026-12-24,Cuti Bersama,collective,
2026-12-31,Penutupan layanan MPP,local,Pemeliharaan akhir tahun
```

Nilai `jenis`: `national`, `collective`, atau `local`. Import memperbarui data jika tanggal yang sama sudah tersedia.

## Refresh dan performa

- Bagian **Kehadiran Hari Ini** diperbarui lokal setiap lima menit ketika terlihat.
- Tombol **Perbarui Data** dapat digunakan tanpa memuat ulang seluruh halaman.
- Riwayat dibatasi maksimal 92 hari per tampilan dan dipaginasi 25 baris.
- Export Excel mendukung maksimal 366 hari.
- Rekap bulanan baru dihitung ketika tab dibuka, memakai agregasi database, dan disimpan dalam cache lima menit.

## Sesi petugas

- Logout otomatis setelah 60 menit tanpa aktivitas manusia di browser.
- Durasi sesi absolut maksimal 12 jam.
- Saat tanggal berganti, sesi lama ditolak pada request berikutnya sehingga petugas harus login kembali.
- Nilai dapat diubah melalui `OPERATOR_IDLE_TIMEOUT` dan `OPERATOR_ABSOLUTE_SESSION` pada `.env`.

## Persiapan setelah deployment

1. Jalankan `php artisan migrate --force`.
2. Periksa semua akun pada **Manajemen Pengguna** dan nonaktifkan akun yang sudah tidak dipakai.
3. Pastikan setiap petugas terhubung ke layanan/loket yang mempunyai instansi.
4. Atur pola lima/enam hari pada seluruh instansi.
5. Isi atau impor kalender hari libur tahun berjalan.
6. Uji login satu akun petugas dan pastikan nama serta jamnya muncul di dashboard hari ini.
