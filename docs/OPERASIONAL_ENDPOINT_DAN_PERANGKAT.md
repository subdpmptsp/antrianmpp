# Daftar Endpoint dan Perangkat Operasional

Dokumen ini menjadi sumber rujukan tim. Perbarui jika ada perubahan URL, IP, printer, zona, atau perangkat.

Inventaris aktual dapat diambil langsung dari sistem tanpa menampilkan token:

```powershell
php artisan app:system-inventory
php artisan app:system-inventory --json
```

Perintah tersebut membaca URL aplikasi, hostname/IP lokal, koneksi database, route penting, pemetaan zona/counter, serta kesiapan token dari konfigurasi dan database aktif. Setelah otorisasi perangkat diaktifkan, kiosk dan TV yang berhasil masuk otomatis tercatat bersama IP, browser, zona, serta waktu terakhir aktif. Nilai token tidak disimpan pada inventaris. Printer tetap memerlukan verifikasi teknisi karena pencetakan berlangsung melalui browser perangkat.

## Informasi aplikasi

| Item | Nilai |
|---|---|
| Nama aplikasi | Web Antrian MPP |
| Environment audit lokal | `local` |
| URL lokal | `http://127.0.0.1` |
| URL produksi | **ISI URL PRODUKSI** |
| Branch stabilisasi | `codex/stabilisasi-antrian` |
| Database | `antrianmpp` |
| Zona waktu bisnis | Asia/Jakarta |

## Endpoint yang terverifikasi

| Fungsi | Method | Endpoint | Akses | Hasil audit |
|---|---:|---|---|---|
| Login admin/petugas | GET | `/admin/login` | Publik | HTTP 200 |
| Redirect admin | GET | `/admin` | Publik | Redirect ke login |
| Cetak antrian kiosk | GET | `/kiosk/cetak-antrian` | Token perangkat saat otorisasi aktif | HTTP 200 |
| Pemilih zona TV resmi | GET | `/tv` | Publik/read-only | HTTP 200 |
| TV zona resmi | GET | `/tv1` sampai `/tv5` | Token per zona saat otorisasi aktif | HTTP 200 |
| Data antrian TV per zona | GET | `/api/tv-display/zone/{zoneId}/queues` | Token TV sesuai zona saat otorisasi aktif | HTTP 200 |
| Status antrian TV | GET | `/api/tv-display/queue-status` | Publik | HTTP 200 |
| Announcement TV | GET | `/api/tv-display/latest-announcement` | Read-only; mendukung `zone_id` dan `after_id` | HTTP 200 |
| Status antrian tanpa ID | GET | `/queue-status` | Publik | Empty state terkontrol, HTTP 200 |
| Halaman SKCK MPP | GET | `/antrian-skck-mpp` | Publik | HTTP 200 |
| Cetak struk uji | GET | `/struk/test` | Admin, nonproduksi | Redirect login/404 produksi |
| Dashboard kiosk panggilan | GET | `/admin/dashboard-call-kiosk` | Login | Redirect ke login jika belum login |

URL TV lama `/tampilan-tv`, `/tv-display-legacy`, `/tv-display-enhanced`, dan `/tv-display-optimized` dipertahankan sebagai redirect ke `/tv`. Template display resmi adalah `tv-simple` melalui `/tv1` sampai `/tv5`.

> Catatan: `{zoneId}` adalah parameter. Daftar zone ID aktif harus dikonfirmasi dari konfigurasi/data produksi, bukan mengandalkan hardcode.

## Perangkat dan jaringan

| ID | Jenis perangkat | Lokasi | IP/Hostname | Port | Fungsi | Status | PIC |
|---|---|---|---|---:|---|---|---|
| DEV-001 | Server aplikasi | **ISI LOKASI** | **ISI IP/HOSTNAME** | 80/443 | Web aplikasi | Perlu konfirmasi | **ISI PIC** |
| DEV-002 | Database MySQL | **ISI LOKASI** | 127.0.0.1 / **ISI PRODUKSI** | 3306 | Database antrian | Perlu konfirmasi | **ISI PIC** |
| DEV-003 | Mesin kiosk | **ISI LOKASI** | **ISI IP/HOSTNAME** | - | Cetak nomor antrian | Perlu konfirmasi | **ISI PIC** |
| DEV-004 | TV display | **ISI LOKASI** | **ISI IP/HOSTNAME** | - | Menampilkan panggilan | Perlu konfirmasi | **ISI PIC** |
| DEV-005 | Printer tiket | **ISI LOKASI** | **ISI NAMA PRINTER** | - | Cetak tiket | Perlu konfirmasi | **ISI PIC** |
| DEV-006 | Printer laporan | **ISI LOKASI** | **ISI NAMA PRINTER** | - | Cetak laporan | Perlu konfirmasi | **ISI PIC** |

## Zona dan counter

| Zone ID | Nama zona | Lokasi | Counter terkait | TV terkait | Status |
|---:|---|---|---|---|---|
| 5 | ZONA 1 | **ISI LOKASI** | Counter bernama ZONA 1 | TV 1 | Data restore terverifikasi; perangkat perlu konfirmasi |
| 20 | ZONA 2 | **ISI LOKASI** | Counter bernama ZONA 2 | TV 2 | Data restore terverifikasi; perangkat perlu konfirmasi |
| 29 | ZONA 3 | **ISI LOKASI** | Counter bernama ZONA 3 | TV 3 | Data restore terverifikasi; perangkat perlu konfirmasi |
| 40 | ZONA 4 | **ISI LOKASI** | Counter bernama ZONA 4 | TV 4 | Data restore terverifikasi; perangkat perlu konfirmasi |
| 109 | ZONA 5 | **ISI LOKASI** | Counter bernama ZONA 5 | TV 5 | Data restore terverifikasi; perangkat perlu konfirmasi |

## Prosedur pemutakhiran

1. Setiap perubahan endpoint/perangkat dicatat di dokumen ini pada hari yang sama.
2. IP dan kredensial tidak ditulis di dokumen publik; simpan rahasia di password manager.
3. Setelah perubahan, lakukan smoke test login, cetak tiket, panggil, layani, selesai, batal, dan TV.
4. PIC melakukan review minimal sebulan sekali.

## Riwayat perubahan

| Tanggal | Perubahan | Oleh |
|---|---|---|
| 2026-08-17 | Dokumen awal dibuat dari hasil audit lokal | Tim stabilisasi |
| 2026-08-17 | Endpoint, otorisasi perangkat, zona, dan template TV resmi diselaraskan dengan implementasi | Codex |
