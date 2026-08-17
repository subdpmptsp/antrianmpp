# Checklist UAT Sistem Antrian MPP

Gunakan satu salinan checklist ini pada setiap pelaksanaan UAT. Isi perangkat, hasil aktual, bukti, dan nama pemeriksa. Jangan menuliskan password atau token perangkat.

## Identitas pelaksanaan

| Item | Nilai |
|---|---|
| Tanggal dan waktu | |
| Environment / URL | |
| Versi / commit | |
| Admin penguji | |
| Petugas penguji | |
| Teknisi | |

## F5-08 — Audio lima zona

| Zona | Browser/perangkat | Panggilan pertama | Recall terdengar | Tidak berulang | Fallback offline | Hasil |
|---:|---|---|---|---|---|---|
| 1 | | | | | | |
| 2 | | | | | | |
| 3 | | | | | | |
| 4 | | | | | | |
| 5 | | | | | | |

Kriteria lulus: setiap panggilan terdengar tepat satu kali pada zona yang benar; recall terdengar sekali lagi; zona lain tidak bersuara; fallback tetap bekerja saat internet diputus.

## F7-07 — Printer, PDF, QR, dan scan mobile

| Item | Model/perangkat | Nomor sama | Layanan sama | Terbaca | Potong kertas | Hasil |
|---|---|---|---|---|---|---|
| Printer tiket thermal | | | | | | |
| PDF tiket | | | | | N/A | |
| QR pada tiket | | | | | N/A | |
| Scan Android | | | | | N/A | |
| Scan iPhone/iPad | | | | | N/A | |

Kriteria lulus: nomor dan layanan sama pada layar, PDF, kertas, isi QR, dan hasil scan; QR membuka tiket yang sama dan bukan nomor baru.

## F7-09 — UAT admin dan petugas

| Alur | Admin | Petugas | Hasil aktual | Bukti / catatan | Status |
|---|---|---|---|---|---|
| Login sesuai role | | | | | |
| Cetak tiket | | | | | |
| Panggil | | | | | |
| Recall | | | | | |
| Mulai layanan | | | | | |
| Selesai | | | | | |
| Batal | | | | | |
| TV dan audio | | | | | |
| Monitoring | | | | | |
| Export laporan | | | | | |

## Persetujuan

| Peran | Nama | Keputusan | Tanggal | Tanda tangan |
|---|---|---|---|---|
| Admin | | Lulus / Tidak | | |
| Petugas layanan | | Lulus / Tidak | | |
| Product Owner | | Lulus / Tidak | | |
| Teknisi | | Lulus / Tidak | | |

Semua baris wajib berstatus lulus atau memiliki tiket perbaikan sebelum rilis produksi.
