# Blueprint Fase Implementasi SiolaQ v2

Dokumen ini menjadi acuan urutan pekerjaan pengembangan antrean onsite dan online SiolaQ. Setiap fase harus selesai, diuji, dan direview sebelum masuk fase berikutnya.

Blueprint ini **tidak menjalankan perubahan kode**. Isinya hanya urutan kerja, keputusan desain, risiko, dan acceptance test.

## Tujuan akhir

SiolaQ memiliki satu sistem antrean yang dapat menerima pemohon onsite maupun online, menggunakan urutan panggilan yang adil, mendukung jadwal operasional dan penutupan loket, serta menyediakan analitik dan deployment yang aman.

Prinsip utamanya:

1. Antrean online dan onsite masuk ke satu urutan panggilan.
2. Pendaftaran online hanya reservasi; peserta baru masuk antrean setelah check-in.
3. Struktur data tetap: **Instansi → Layanan → Loket → Akun Petugas**.
4. Zona melekat pada instansi.
5. Modul online dapat dimatikan tanpa menghentikan antrean onsite.
6. Perubahan operasional harus dapat diaudit dan dibatalkan dengan aman.

## Urutan fase

| Fase | Fokus | Hasil utama | Risiko jika dilewati |
|---|---|---|---|
| 0 | Audit dan keputusan desain | Baseline data, kontrak status, daftar keputusan | Migrasi salah dan perubahan saling bertabrakan |
| 1 | Fondasi master data | Relasi instansi, layanan, loket, petugas konsisten | Data yatim dan zona tidak konsisten |
| 2 | Kontrak antrean onsite | Waktu masuk, sumber, status, dan lock database | Nomor ganda atau urutan tidak adil |
| 3 | Jadwal dan cutoff | Satu fungsi status loket untuk semua layar | Kiosk, petugas, dan admin berbeda status |
| 4 | Unifikasi antrean | Panggilan FIFO online-onsite yang konsisten | Antrean online/onsite terpisah |
| 5 | Modul online dan check-in | Reservasi, sesi, QR, check-in aman | Online menjadi prioritas atau tiket ganda |
| 6 | UI operasional dan transparansi | Badge sumber, status, dan pesan publik | Petugas/pemohon tidak memahami status |
| 7 | Analitik | Rekap antrean dan kehadiran yang valid | Grafik menyesatkan karena definisi berbeda |
| 8 | Pengujian dan hardening | Hasil uji concurrency, restore, dan soak test | Masalah baru muncul saat operasional |
| 9 | Deployment dan operasional | SOP rilis, backup, monitoring, dan rollback | Sulit pulih saat server atau update bermasalah |

---

## Fase 0 — Audit dan keputusan desain

### Tujuan

Memastikan kondisi database dan perilaku aplikasi saat ini dipahami sebelum migration atau fitur baru dibuat.

### Pekerjaan

- Inventaris tabel, relasi, route, job, scheduler, dan perangkat yang memakai aplikasi.
- Audit baris dengan `instansi_id`, `service_id`, `counter_id`, atau akun petugas yang kosong.
- Audit loket yang memiliki lebih dari satu layanan melalui relasi utama dan layanan tambahan.
- Audit instansi yang mempunyai nama zona berbeda tetapi secara makna sama.
- Audit status antrean yang sudah ada dan pemetaan ke status baru.
- Tentukan apakah layanan tambahan pada loket tetap didukung.
- Tentukan apakah urutan antrean murni FIFO atau memakai rasio online-onsite.
- Tetapkan timezone resmi: `Asia/Jakarta`.
- Tetapkan definisi tiket diterbitkan, check-in, dipanggil, dilayani, selesai, dan batal.
- Buat backup database sebelum audit lanjutan.

### Keputusan wajib sebelum lanjut

1. Satu loket boleh melayani satu atau beberapa layanan?
2. Apakah hari libur selalu mengalahkan override manual?
3. Apakah panggilan memakai FIFO murni atau rasio eksplisit?
4. Berapa lama data NIK dan nomor HP online disimpan?
5. Apakah Zona 4 memakai nama instansi sebagai format TTS uji coba?

### Acceptance test

- Laporan audit tersimpan.
- Tidak ada data yang akan terkena constraint tanpa daftar remediasi.
- Backup dapat dibuka dan diverifikasi checksum-nya.
- Keputusan desain disetujui sebelum migration dibuat.

---

## Fase 1 — Fondasi master data

### Tujuan

Menjamin relasi master data tidak menghasilkan data yatim dan zona memiliki satu sumber kebenaran.

### Pekerjaan

- Pastikan `instansis.zone` menjadi sumber zona resmi.
- `services.instansi_id` wajib terisi.
- `counters.instansi_id` wajib terisi.
- Tetapkan aturan `counters.service_id` sebagai layanan utama jika layanan tambahan tetap dipertahankan.
- Tambahkan foreign key pada level database setelah data lama dibersihkan.
- Gunakan status nonaktif/arsip, bukan penghapusan fisik master data.
- Cegah instansi, layanan, atau loket dinonaktifkan jika masih memiliki antrean aktif tanpa konfirmasi yang jelas.
- Sediakan aksi “Tambah layanan untuk instansi ini” dengan `instansi_id` otomatis.
- Pastikan akun operator memiliki loket; akun admin tidak perlu memiliki loket.
- Tambahkan index pada foreign key yang sering dipakai.

### Strategi migration

1. Tambahkan kolom nullable bila diperlukan.
2. Jalankan audit data.
3. Perbaiki data kosong secara manual atau melalui migration remediasi terpisah.
4. Tambahkan foreign key.
5. Ubah menjadi `NOT NULL` setelah seluruh data valid.
6. Verifikasi ulang route admin, kiosk, petugas, dan TV.

### Acceptance test

- Layanan tanpa instansi ditolak di UI dan database.
- Loket aktif tanpa instansi atau layanan utama ditolak.
- Operator tanpa loket tidak dapat membuka halaman panggilan.
- Zona pada kiosk, petugas, dan TV berasal dari instansi yang sama.
- Menonaktifkan master data tidak menghapus histori antrean.

---

## Fase 2 — Kontrak antrean onsite

### Tujuan

Membuat antrean onsite aman sebagai fondasi yang nantinya dipakai online.

### Struktur yang dibutuhkan

- `waktu_masuk_antrean`: timestamp immutable ketika baris antrean dibuat.
- `sumber`: `onsite` atau `online`, default `onsite`.
- Status antrean yang konsisten, misalnya `waiting`, `printing`, `called`, `serving`, `finished`, `canceled`.
- Index gabungan minimal pada layanan, status, loket, dan waktu masuk.

### Pekerjaan

- Pisahkan waktu dibuat dari waktu masuk antrean jika kebutuhan bisnis memerlukannya.
- Generate nomor menggunakan transaction dan row lock.
- Pastikan reset nomor harian tidak menghasilkan nomor ganda ketika dua kiosk menekan bersamaan.
- Tetapkan apakah tiket `printing` yang gagal dikembalikan ke antrean atau dibatalkan.
- Pastikan semua perubahan status memiliki timestamp yang sesuai.

### Acceptance test

- Dua request bersamaan tidak menghasilkan nomor duplikat.
- Urutan `waiting` selalu berdasarkan `waktu_masuk_antrean`.
- Tiket batal tidak ikut dipanggil.
- Restart Apache/MySQL tidak mengubah status antrean yang sudah tersimpan.
- Pemanggilan ulang tidak membuat baris antrean baru.

---

## Fase 3 — Jadwal operasional, hari libur, cutoff, dan auto-reopen

### Tujuan

Menyediakan satu sumber status loket yang dipakai admin, petugas, kiosk, TV, dan API.

### Data

- Jadwal mingguan: hari, aktif, jam buka, jam tutup.
- Hari libur: tanggal dan keterangan.
- Cutoff penerbitan nomor.
- Override loket: default, tutup manual, istirahat sementara, atau buka khusus bila memang diperlukan.
- Persetujuan admin dan audit trail.

### Urutan evaluasi yang direkomendasikan

1. Override manual yang disetujui.
2. Hari libur, kecuali ada aturan buka khusus yang eksplisit.
3. Jadwal mingguan.
4. Cutoff penerbitan nomor.
5. Status antrean aktif yang sudah terlanjur masuk tetap dapat dipanggil sesuai kebijakan.

Urutan final harus ditetapkan secara eksplisit karena hari libur dan override manual bisa bertabrakan.

### Pekerjaan

- Buat satu service `cekStatusLoket(loketId, waktu)`, bukan logika terpisah di setiap halaman.
- Kembalikan status, alasan, jam buka, jam tutup, dan apakah nomor baru boleh dibuat.
- Jalankan auto-reopen pada awal hari operasional sesuai jadwal, termasuk pengaman pukul 00.00 bila itu memang kebijakan yang disetujui.
- Sinkronkan status service/loket dengan kiosk sehingga loket yang disetujui tutup tidak menerima nomor baru.
- Jadwalkan scheduler setiap satu menit melalui Windows Task Scheduler.
- Tangani jadwal lintas tengah malam dan perubahan timezone dengan test khusus.

### Acceptance test

- Status yang sama terlihat di admin, petugas, kiosk, dan TV.
- Persetujuan tutup loket langsung menghentikan nomor baru.
- Auto-reopen mengembalikan loket ke status default tanpa membuka layanan yang memang nonaktif.
- Hari libur tidak menerima nomor baru.
- Cutoff menghentikan nomor baru tetapi tidak menghapus antrean yang sudah ada.
- Pengajuan menggantung memiliki batas kedaluwarsa dan tidak memblokir hari berikutnya.

---

## Fase 4 — Unifikasi antrean onsite dan online

### Tujuan

Memastikan petugas mengambil antrean dari satu urutan yang adil.

### Aturan inti

- Tombol “Panggil berikutnya” membaca satu sumber antrean.
- Query tidak memisahkan `sumber=onsite` dan `sumber=online` kecuali ada aturan rasio resmi.
- Urutan default: `waktu_masuk_antrean ASC`, lalu `id ASC` sebagai tie-breaker.
- Jika rasio diperlukan, simpan sebagai konfigurasi resmi per loket; jangan menjadi pilihan manual petugas.
- TV menampilkan urutan gabungan.

### Pekerjaan

- Tambahkan transaction dan lock saat mengambil antrean berikutnya.
- Cegah dua petugas memanggil antrean yang sama.
- Tambahkan badge “Online” atau “Onsite” sebagai informasi saja.
- Catat sumber pada histori panggilan dan laporan.

### Acceptance test

- Antrean online tidak melompati antrean onsite yang lebih dahulu check-in.
- Antrean onsite tetap berjalan ketika modul online dimatikan.
- Dua loket tidak dapat mengambil baris antrean yang sama.
- Panggilan ulang tidak mengubah posisi antrean lain.

---

## Fase 5 — Modul online, sesi, reservasi, dan check-in

### Tujuan

Membuat online sebagai reservasi waktu kedatangan, bukan jalur prioritas.

### Data utama

- Event: instansi, periode, status, dan layanan.
- Sesi: waktu mulai/selesai, kapasitas total, kuota online, kuota walk-in.
- Pendaftaran: data peserta, status, token, dan audit timestamp.
- Link publik dan token QR yang acak.

### Alur resmi

1. Admin mengaktifkan modul online.
2. Admin membuat event dan sesi.
3. Pemohon mendaftar pada sesi yang tersedia.
4. Sistem memberikan kode reservasi, bukan posisi antrean.
5. Pemohon check-in dalam jendela waktu sesi.
6. Check-in yang berhasil membuat satu baris antrean gabungan dengan `sumber=online` dan `waktu_masuk_antrean=now()`.
7. Petugas memanggilnya melalui antrean gabungan.

### Keamanan dan integritas

- Check-in harus idempotent.
- Tiket kadaluarsa menjadi `tidak_hadir` secara otomatis.
- Cegah duplikasi NIK/HP per event sesuai kebijakan privasi.
- Terapkan rate limit, token sekali pakai, dan audit log.
- Jangan tampilkan NIK penuh pada TV atau URL.
- Sediakan toggle global untuk mematikan modul online tanpa menyentuh onsite.

### Acceptance test

- Pendaftaran tidak langsung masuk antrean petugas.
- Check-in di luar sesi ditolak dengan alasan jelas.
- Dua request check-in bersamaan hanya menghasilkan satu antrean.
- Modul online mati: kiosk onsite dan pemanggilan onsite tetap berjalan.
- Link QR nonaktif tidak dapat dipakai.

---

## Fase 6 — UI operasional, TTS, kiosk, dan TV

### Tujuan

Membuat perubahan status dan sumber antrean mudah dipahami pengguna.

### Pekerjaan

- Tampilkan badge sumber online/onsite di halaman petugas.
- Tampilkan status layanan yang konsisten di kiosk dan TV.
- Pastikan nomor tidak dapat dibuat ketika layanan/loket ditutup.
- Gunakan satu formatter pelafalan angka dan kode di seluruh browser TTS.
- Untuk uji coba Zona 4, gunakan format instansi:

  > “Nomor antrean empat ka nol satu, silakan menuju loket empat ka satu, Dinas Tenaga Kerja.”

- Zona lain tetap menggunakan format nama layanan sampai ada keputusan baru.
- Sediakan fallback jika browser tidak memiliki suara Bahasa Indonesia.

### Acceptance test

- Format nomor `01`, `10`, `12`, dan `20` terdengar benar.
- Kode `4K-10` dibaca “empat ka sepuluh”.
- Nama instansi Zona 4 hanya digunakan pada kondisi uji coba Zona 4.
- Zona lain tidak ikut berubah.
- Browser refresh atau berpindah halaman tidak menggandakan suara.

---

## Fase 7 — Dashboard analitik

### Analitik antrean

- Tiket per jam dan per hari.
- Perbandingan online dan onsite.
- Heatmap hari × jam.
- Rata-rata waktu tunggu dan waktu layanan.
- Funnel online: terdaftar → check-in → dipanggil → selesai.
- Filter periode, zona, instansi, layanan, dan sumber.
- Garis rata-rata dan pembanding periode sebelumnya.

### Rekap kehadiran petugas

- Stacked bar hadir/tidak hadir per hari.
- Perhitungan berdasarkan kalender kerja instansi, termasuk perbedaan 5 hari dan 6 hari kerja.
- Hari libur dan tanggal masa depan tidak dihitung sebagai tidak hadir.
- Ranking instansi dengan ketidakhadiran tertinggi.
- Tabel petugas dengan pola tidak hadir berulang.
- Ekspor Excel dengan data mentah dan ringkasan.

### Acceptance test

- Filter instansi menghasilkan data yang benar-benar berubah.
- Instansi 5 hari kerja tidak dibandingkan mentah dengan instansi 6 hari kerja.
- Nama instansi panjang tetap terbaca.
- Chart tidak memakai maksimum Y statis yang dapat memotong data.
- Angka pada kartu ringkasan sama dengan data ekspor.

---

## Fase 8 — Pengujian dan hardening

### Pengujian wajib

- Concurrent generate nomor pada layanan yang sama.
- Concurrent check-in tiket online.
- Dua petugas menekan panggil berikutnya bersamaan.
- Persetujuan tutup loket ketika kiosk sedang aktif.
- Auto-reopen setelah server mati semalaman.
- Koneksi jaringan terputus saat cetak tiket.
- TV dan kiosk menyala minimal 8 jam.
- Refresh browser dan pembukaan banyak perangkat.
- Restore database dari backup.

### Observability

- Log correlation ID untuk generate, check-in, panggil, dan cetak.
- Log alasan status loket.
- Monitor error 5xx, waktu respons, dan jumlah polling per menit.
- Alarm jika scheduler tidak berjalan.

### Acceptance test

- Tidak ada nomor atau check-in ganda.
- Tidak ada memory leak yang terus naik selama soak test.
- Restore backup menghasilkan aplikasi yang dapat dibuka dan data yang konsisten.
- Semua kegagalan penting memiliki pesan yang dapat ditindaklanjuti.

---

## Fase 9 — Deployment dan operasional

### Baseline server

- Apache dan MySQL aktif otomatis.
- IP server LAN statis.
- Windows Firewall membuka port yang diperlukan.
- `.env` production menggunakan `APP_DEBUG=false`.
- File `.env` asli tidak masuk Git.
- SSL digunakan untuk domain yang memakai kamera/QR check-in.

### Backup

- `mysqldump` otomatis harian.
- Salinan backup berada di lokasi atau drive berbeda.
- Retensi backup ditetapkan, misalnya harian dan mingguan.
- Restore diuji berkala, bukan hanya membuat file backup.

### SOP rilis

1. Backup database.
2. Catat commit yang akan dirilis.
3. Aktifkan maintenance singkat bila diperlukan.
4. `git pull`.
5. `composer install --no-dev`.
6. `npm run build`.
7. `php artisan migrate`.
8. `php artisan optimize:clear`.
9. `php artisan optimize`.
10. Smoke test kiosk, petugas, TV, admin, dan API.
11. Nonaktifkan maintenance.
12. Siapkan rollback ke commit sebelumnya bila smoke test gagal.

### Acceptance test operasional

- URL publik dan panel admin dapat dibuka.
- Kiosk bisa menerbitkan nomor.
- Petugas bisa memanggil dan menyelesaikan antrean.
- TV menampilkan panggilan dan suara.
- Status tutup/buka loket sinkron.
- Backup terbaru dapat diidentifikasi dan diverifikasi.

---

## Checklist persetujuan sebelum coding

- [ ] Keputusan satu atau banyak layanan per loket sudah final.
- [ ] Prioritas hari libur dan override manual sudah final.
- [ ] FIFO atau rasio online-onsite sudah final.
- [ ] Format TTS Zona 4 sudah disetujui untuk uji coba.
- [ ] Retensi data peserta online sudah disetujui.
- [ ] Strategi backup dan restore sudah disiapkan.
- [ ] Fase yang akan dikerjakan sudah dipilih; tidak mengerjakan semua fase sekaligus.

## Rekomendasi titik mulai

Mulai dari **Fase 0**, lalu lanjut ke **Fase 1 dan Fase 2**. Jangan membuat modul pendaftaran online sebelum kontrak antrean onsite, status loket, dan mekanisme lock database sudah lulus pengujian.
