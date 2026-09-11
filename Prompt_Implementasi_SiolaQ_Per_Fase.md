# Paket Prompt Implementasi SiolaQ per Fase

Dokumen ini berisi prompt siap salin untuk merealisasikan pengembangan antrean onsite dan online SiolaQ secara bertahap, aman, dan mudah direview.

> **Penting:** Jalankan hanya satu prompt dalam satu waktu. Jangan menggabungkan seluruh fase dalam satu permintaan. Selesaikan review, implementasi, pengujian, dan persetujuan pada satu fase sebelum membuka fase berikutnya.

## Urutan penggunaan

1. Gunakan **Prompt Aturan Umum** sebagai konteks awal setiap task/sesi baru.
2. Jalankan prompt **Review** pada fase yang sedang dikerjakan.
3. Periksa hasil review dan putuskan hal yang masih ambigu.
4. Setelah disetujui, jalankan prompt **Implementasi** pada fase yang sama.
5. Minta laporan hasil pengujian dan daftar perubahan.
6. Jangan lanjut jika acceptance test fase tersebut belum lulus.
7. Buat commit terpisah untuk setiap fase yang telah stabil.

---

## Prompt Aturan Umum — tempel pada awal setiap task

```text
Anda bekerja pada project Laravel SiolaQ yang sudah digunakan untuk operasional antrean MPP SIOLA.

Aturan kerja wajib:
1. Pelajari struktur dan kode existing sebelum mengubah apa pun. Jangan berasumsi nama tabel, kolom, relasi, route, status, atau versi package.
2. Baca AGENTS.md atau instruksi repository jika tersedia.
3. Pertahankan fitur yang sudah berjalan: kiosk onsite, halaman petugas, TV per zona, audio/TTS, persetujuan tutup loket, dashboard admin, kehadiran, dan event yang sudah ada.
4. Jangan menghapus atau menimpa perubahan pengguna yang tidak berhubungan dengan tugas.
5. Jangan menggunakan git reset --hard, checkout paksa, drop database, migrate:fresh, db:wipe, truncate, atau seeder terhadap database operasional.
6. Jangan menjalankan migration pada database produksi. Buat migration dan uji hanya pada database testing yang benar-benar terisolasi.
7. Sebelum test database, pastikan APP_ENV=testing dan nama database khusus testing atau SQLite memory. Batalkan test jika mengarah ke database produksi.
8. Gunakan pola expand-and-contract untuk perubahan schema: tambah struktur baru → backfill/audit → validasi → baru perketat constraint.
9. Semua operasi yang bisa dipanggil bersamaan harus memakai transaction, lock, unique constraint, dan idempotency sesuai kebutuhan.
10. Semua perubahan status penting harus memiliki audit trail dan timestamp.
11. Jangan memasukkan .env, password, token, NIK, nomor HP, file backup SQL, atau data akun ke Git.
12. Jangan push GitHub kecuali diminta secara eksplisit.
13. Gunakan apply_patch untuk perubahan file dan lakukan perubahan sekecil mungkin.
14. Jalankan pemeriksaan syntax, formatting, test terarah, dan git diff --check setelah implementasi.
15. Jika menemukan konflik desain atau data existing yang berisiko, berhenti dan laporkan. Jangan mengambil keputusan bisnis sendiri.

Prinsip bisnis yang harus dipertahankan:
- Struktur sumber kebenaran: Instansi → Layanan → Loket → Akun Petugas.
- Zona melekat pada instansi.
- Online adalah reservasi waktu kedatangan, bukan prioritas antrean.
- Peserta online baru masuk antrean gabungan setelah check-in berhasil.
- Online dan onsite menggunakan satu urutan panggilan berdasarkan waktu_masuk_antrean.
- Modul online harus dapat dimatikan tanpa mengganggu onsite.

Pada akhir pekerjaan, laporkan:
- file yang berubah;
- migration/schema yang ditambahkan;
- perilaku sebelum dan sesudah;
- pengujian yang dijalankan dan hasilnya;
- pengujian yang tidak dapat dijalankan beserta alasannya;
- risiko atau pekerjaan lanjutan;
- konfirmasi bahwa .env dan data sensitif tidak masuk perubahan.
```

---

# Fase 0 — Audit dan keputusan desain

## Prompt 0A — audit read-only

```text
Gunakan Prompt Aturan Umum SiolaQ.

Kerjakan Fase 0 dalam mode READ-ONLY. Jangan mengubah file, schema, database, konfigurasi, atau Git.

Tujuan:
Membuat baseline kondisi aktual aplikasi sebelum fitur antrean online dan unifikasi dikerjakan.

Periksa:
1. Versi Laravel, PHP, Filament, database, dan package utama.
2. Model, migration, foreign key, nullable column, index, enum/string status, dan relasi untuk instansi, layanan, loket/counter, user/operator, queue, schedule, holiday, closure request, dan event.
3. Data model existing untuk layanan utama dan layanan tambahan counter_service.
4. Seluruh tempat yang membuat nomor antrean, melakukan check-in, memanggil antrean berikutnya, memanggil ulang, dan mengubah status antrean.
5. Seluruh logika status buka/tutup pada admin, kiosk, petugas, TV, scheduler, dan service class.
6. Kondisi zona dan sumber kebenarannya.
7. Status antrean existing dan timestamp yang digunakan.
8. Test yang sudah tersedia dan pengaman test terhadap database produksi.
9. Route publik, route ber-auth, middleware perangkat, polling, dan audio/TTS.
10. Perubahan Git yang belum dicommit; jangan sentuh perubahan tersebut.

Jika akses read-only database aman tersedia, buat query audit tanpa mengubah data untuk menemukan:
- layanan tanpa instansi;
- loket tanpa instansi atau layanan utama;
- operator tanpa loket;
- zona kosong/tidak konsisten;
- counter_service yang bertentangan dengan service_id;
- status queue di luar daftar resmi;
- duplikasi nomor antrean pada hari dan layanan yang sama.

Output yang diminta:
- diagram hubungan data aktual;
- daftar gap antara kondisi aktual dan target;
- daftar risiko P0/P1/P2;
- keputusan bisnis yang masih harus dijawab;
- rekomendasi urutan migration;
- daftar test yang harus ada;
- kesimpulan GO/NO-GO untuk Fase 1.

Berhenti setelah laporan. Jangan implementasikan perbaikan.
```

## Prompt 0B — dokumentasikan keputusan final

```text
Berdasarkan laporan audit Fase 0 dan keputusan pengguna, buat dokumen keputusan arsitektur SiolaQ tanpa mengubah kode aplikasi.

Dokumen wajib menetapkan:
1. Apakah satu loket boleh melayani satu atau beberapa layanan.
2. Peran service_id sebagai layanan utama dan counter_service sebagai layanan tambahan bila tetap dipakai.
3. Urutan prioritas override manual, hari libur, jadwal mingguan, dan cutoff.
4. FIFO murni atau rasio online-onsite.
5. Daftar status antrean dan transisi legal.
6. Definisi waktu_masuk_antrean.
7. Kebijakan retensi dan perlindungan NIK/nomor HP.
8. Format TTS per zona, termasuk uji coba Zona 4.
9. Kriteria rollback setiap fase.

Simpan sebagai Markdown yang dapat direview. Jangan menjalankan migration atau perubahan kode.
```

### Gerbang Fase 0

- [ ] Audit data dan kode selesai.
- [ ] Keputusan satu/banyak layanan per loket sudah final.
- [ ] Prioritas status operasional sudah final.
- [ ] FIFO/rasio sudah final.
- [ ] Daftar status dan transisi antrean sudah final.
- [ ] GO untuk Fase 1 diberikan.

---

# Fase 1 — Fondasi master data

## Prompt 1A — review rencana migration

```text
Gunakan Prompt Aturan Umum dan dokumen keputusan Fase 0.

Review Fase 1 tanpa mengeksekusi migration dan tanpa mengubah database.

Tujuan target:
- instansis.zone menjadi sumber zona resmi;
- services.instansi_id wajib dan memiliki foreign key;
- counters.instansi_id wajib dan memiliki foreign key;
- aturan service_id/counter_service mengikuti keputusan Fase 0;
- operator wajib memiliki counter_id, admin tidak;
- master data menggunakan nonaktif/arsip, bukan hard delete;
- histori operasional tetap utuh.

Pelajari migration existing agar tidak membuat kolom atau constraint duplikat. Buat rencana expand-and-contract yang menyebutkan:
1. migration tambah/perbaikan schema;
2. query audit sebelum constraint;
3. cara backfill yang tidak menebak data;
4. baris yang harus diperbaiki manual;
5. urutan penambahan index, foreign key, dan NOT NULL;
6. rollback yang aman;
7. dampak terhadap Filament resource, validation, import/export, seeder, dan test.

Output berupa rencana file-per-file dan migration-per-migration. Berhenti sebelum implementasi.
```

## Prompt 1B — implementasi fondasi master data

```text
Implementasikan Fase 1 sesuai rencana yang telah disetujui.

Batas pekerjaan:
1. Buat migration expand-and-contract; jangan mengubah migration lama yang sudah pernah digunakan produksi.
2. Buat command/query audit yang read-only dan menampilkan baris penghambat secara jelas.
3. Jangan otomatis menebak instansi, layanan, zona, atau loket untuk data kosong.
4. Tambahkan validation aplikasi dan constraint database secara konsisten.
5. Pertahankan histori queue ketika master data dinonaktifkan/diarsipkan.
6. Tambahkan aksi “Tambah layanan untuk instansi ini” dengan instansi terisi otomatis bila sesuai desain existing.
7. Tambahkan test untuk data yatim, foreign key, operator tanpa loket, arsip, dan konsistensi zona.

Jangan menjalankan migration pada database produksi. Uji pada database testing terisolasi. Jika test database terisolasi tidak tersedia, lakukan syntax/static check dan laporkan bahwa migration belum dieksekusi.

Berhenti setelah implementasi, diff review, dan laporan test. Jangan lanjut ke Fase 2.
```

### Gerbang Fase 1

- [ ] Tidak ada layanan/loket yatim.
- [ ] Sumber zona konsisten.
- [ ] Histori tidak terhapus saat arsip.
- [ ] Migration forward dan rollback diuji terisolasi.
- [ ] GO untuk Fase 2 diberikan.

---

# Fase 2 — Kontrak antrean onsite dan keamanan concurrency

## Prompt 2A — review alur antrean onsite

```text
Gunakan Prompt Aturan Umum dan hasil Fase 1.

Review seluruh alur antrean onsite tanpa mengubah kode.

Petakan:
- endpoint/tombol generate tiket;
- penentuan nomor berikutnya;
- proses printing/confirm/fail;
- query panggil berikutnya;
- panggil ulang;
- serve/finish/cancel;
- reset nomor harian;
- lock dan transaction existing;
- unique constraint dan index existing.

Rancang target:
- waktu_masuk_antrean immutable;
- sumber onsite/online, default onsite;
- status dan transisi legal;
- tie-breaker id setelah waktu_masuk_antrean;
- idempotency pada generate/printing;
- transaction dan lock untuk nomor dan pemanggilan.

Jelaskan risiko perubahan terhadap tiket lama dan laporan. Berikan migration plan, service plan, index plan, test matrix concurrency, serta rollback. Berhenti sebelum implementasi.
```

## Prompt 2B — implementasi kontrak antrean onsite

```text
Implementasikan Fase 2 sesuai review yang telah disetujui.

Persyaratan:
1. Tambahkan waktu_masuk_antrean dan sumber secara backward-compatible.
2. Backfill data lama menggunakan aturan yang disetujui dan terdokumentasi.
3. Gunakan transaction dan database lock saat menentukan nomor berikutnya.
4. Tambahkan unique constraint yang tepat untuk mencegah nomor harian ganda tanpa merusak format nomor existing.
5. Pastikan printing gagal tidak menciptakan nomor baru secara diam-diam.
6. Pastikan panggil ulang tidak membuat queue baru atau mengubah posisi queue lain.
7. Tambahkan index query antrean menunggu.
8. Tambahkan test concurrent generate, duplicate request, transition status ilegal, dan restart persistence.

Jangan menyentuh modul online pada fase ini. Jangan menjalankan stress test ke server produksi.

Berhenti setelah test dan laporan. Jangan lanjut ke jadwal atau online.
```

### Gerbang Fase 2

- [ ] Tidak ada nomor ganda saat request bersamaan.
- [ ] Status/transisi antrean tervalidasi.
- [ ] Antrean lama tetap terbaca.
- [ ] Query utama memakai index yang tepat.
- [ ] GO untuk Fase 3 diberikan.

---

# Fase 3 — Jadwal, cutoff, override, dan auto-reopen

## Prompt 3A — review sumber status loket

```text
Gunakan Prompt Aturan Umum dan hasil Fase 0–2.

Review semua logika buka/tutup yang tersebar di admin, service, command scheduler, petugas, kiosk, TV, dan API. Jangan mengubah kode.

Targetkan satu service keputusan status loket yang menerima loket dan waktu, lalu mengembalikan:
- status efektif: buka, cutoff, tutup, atau istirahat;
- boleh/tidak menerbitkan nomor baru;
- antrean lama tetap boleh/tidak dipanggil;
- alasan;
- jam buka kembali;
- sumber keputusan: override, hari libur, jadwal, cutoff, atau status master.

Gunakan prioritas yang telah disetujui pada Fase 0. Identifikasi konflik antara is_active, is_accepting_queues, closure request, override, schedule, holiday, dan auto-reopen.

Buat rencana konsolidasi bertahap, test matrix waktu, dan strategi agar kiosk/admin/petugas tidak berbeda status. Berhenti sebelum implementasi.
```

## Prompt 3B — implementasi sumber status tunggal

```text
Implementasikan Fase 3 sesuai rencana yang disetujui.

Persyaratan:
1. Gunakan satu service status operasional sebagai sumber kiosk, admin, petugas, TV, dan API.
2. Pertahankan kemampuan memanggil antrean lama ketika nomor baru ditutup, sesuai kebijakan.
3. Persetujuan tutup harus langsung menghentikan generate nomor di kiosk.
4. Auto-reopen tidak boleh mengaktifkan master data yang memang dinonaktifkan admin.
5. Pengajuan yang menggantung harus kedaluwarsa pada batas yang disetujui.
6. Tambahkan audit log untuk override dan auto-reopen.
7. Pastikan scheduler idempotent jika dieksekusi lebih dari sekali.
8. Tambahkan test boundary waktu: sebelum buka, tepat buka, sebelum cutoff, tepat cutoff, tutup, tengah malam, hari berikutnya, dan hari libur.

Jangan mengubah urutan antrean atau membangun fitur online. Berhenti setelah test dan laporan.
```

### Gerbang Fase 3

- [ ] Status admin, kiosk, petugas, TV, dan API sama.
- [ ] Cutoff tidak menghapus antrean existing.
- [ ] Auto-reopen tidak membuka master nonaktif.
- [ ] Scheduler idempotent.
- [ ] GO untuk Fase 4 diberikan.

---

# Fase 4 — Mesin antrean gabungan

## Prompt 4A — review algoritma pemanggilan

```text
Gunakan Prompt Aturan Umum serta kontrak antrean Fase 2 dan status operasional Fase 3.

Review algoritma “Panggil berikutnya” tanpa mengubah kode.

Target default:
- satu tabel/sumber queue;
- status waiting;
- scope layanan yang boleh dipanggil loket;
- order waktu_masuk_antrean ASC, id ASC;
- tanpa pemisahan sumber online/onsite;
- transaction dan row lock;
- dua petugas tidak dapat mengambil queue yang sama.

Periksa pengaruh layanan utama dan layanan tambahan loket. Jika keputusan Fase 0 memilih rasio, rancang state rasio deterministik dan otomatis; jangan memberi petugas tombol memilih sumber.

Output:
- pseudocode query;
- batas transaction;
- kebutuhan index;
- perilaku saat queue sudah diambil petugas lain;
- test concurrency;
- dampak pada panggil ulang, TV, dan statistik.

Berhenti sebelum implementasi.
```

## Prompt 4B — implementasi antrean gabungan

```text
Implementasikan mesin pemanggilan antrean gabungan yang telah disetujui.

Persyaratan:
1. Semua panggilan berikutnya memakai waktu_masuk_antrean dan id sebagai tie-breaker.
2. Jangan filter berdasarkan sumber untuk FIFO murni.
3. Gunakan transaction dan lock saat memilih dan mengubah queue.
4. Tangani retry/deadlock secara terbatas dan tercatat.
5. Pertahankan authorization agar operator hanya memanggil layanan yang diizinkan loketnya.
6. Tambahkan badge sumber sebagai data, tetapi jangan mengubah desain besar UI pada fase ini.
7. Tambahkan test dua operator bersamaan, layanan tambahan, queue kosong, queue batal, dan panggil ulang.

Jangan membuat form pendaftaran online. Berhenti setelah test dan laporan.
```

### Gerbang Fase 4

- [ ] Tidak ada queue dipanggil dua loket sekaligus.
- [ ] FIFO/tata rasio sesuai keputusan.
- [ ] Onsite existing tetap bekerja.
- [ ] Authorization loket tetap berlaku.
- [ ] GO untuk Fase 5 diberikan.

---

# Fase 5 — Modul online, event, sesi, dan check-in

## Prompt 5A — review modul event existing

```text
Gunakan Prompt Aturan Umum dan hasil Fase 0–4.

Review implementasi event/antrean online yang sudah ada sebelum menambah tabel atau route baru. Jangan mengubah kode.

Periksa apakah sudah tersedia:
- event, session/slot, registration, ticket, QR, TV event;
- token publik;
- kapasitas dan kuota;
- check-in;
- status peserta;
- data NIK/HP dan cara penyimpanannya;
- route ganda;
- authorization dan rate limit.

Bandingkan dengan target reservasi → check-in → insert satu queue sumber online. Usulkan reuse atau migration dari struktur existing; jangan membuat modul paralel jika fungsi serupa sudah ada.

Buat threat model ringkas untuk NIK, HP, token QR, enumeration, replay check-in, dan spam. Buat rencana idempotency dan unique constraint. Berhenti sebelum implementasi.
```

## Prompt 5B — implementasi modul online

```text
Implementasikan Fase 5 dengan memanfaatkan struktur event existing sebanyak mungkin.

Persyaratan:
1. Toggle global mematikan route/fitur online tanpa mempengaruhi onsite.
2. Pendaftaran hanya membuat reservasi, bukan queue waiting.
3. Check-in memakai transaction, lock, dan idempotency.
4. Satu pendaftaran hanya dapat menghasilkan satu queue.
5. Queue online menggunakan waktu_masuk_antrean saat check-in berhasil.
6. Validasi kapasitas dan duplikasi dilakukan dengan constraint database serta pesan aplikasi.
7. Token publik acak dan tidak mudah ditebak; jangan menaruh NIK di URL.
8. Terapkan rate limit dan audit log.
9. Masking data peserta pada TV aktif secara default.
10. Tambahkan test replay check-in, concurrency, slot penuh, terlambat, terlalu awal, token invalid, toggle mati, dan onsite tetap berjalan.

Jangan mengubah algoritma antrean yang sudah lulus Fase 4 kecuali dibutuhkan untuk bug yang terukur dan dilaporkan terlebih dahulu.

Berhenti setelah test dan laporan.
```

### Gerbang Fase 5

- [ ] Daftar online belum masuk queue sebelum check-in.
- [ ] Check-in ganda tidak membuat queue ganda.
- [ ] Toggle online tidak mengganggu onsite.
- [ ] Data pribadi tidak bocor di URL/TV/log.
- [ ] GO untuk Fase 6 diberikan.

---

# Fase 6 — UI petugas, kiosk, TV, dan TTS

## Prompt 6A — review UX lintas perangkat

```text
Gunakan Prompt Aturan Umum dan hasil Fase 1–5.

Review UI admin, petugas, kiosk, TV, dan browser TTS tanpa mengubah kode.

Periksa:
- konsistensi status buka/tutup;
- badge online/onsite;
- pesan check-in dan penolakan;
- tampilan di layar kecil dan TV 16:9;
- dark/light mode untuk halaman yang relevan;
- duplikasi listener Livewire/JavaScript;
- scoping audio per zona;
- normalisasi angka, huruf, kode, dan singkatan TTS;
- fallback suara Bahasa Indonesia.

Rancang perubahan minimal dan urutkan berdasarkan risiko. Untuk Zona 4 gunakan format uji coba nama instansi hanya jika flag zona/kode loket cocok. Berhenti sebelum implementasi.
```

## Prompt 6B — implementasi UX dan TTS

```text
Implementasikan Fase 6 sesuai review yang disetujui.

Persyaratan:
1. Badge sumber hanya informatif dan tidak mengubah urutan.
2. Kiosk tidak dapat generate ketika status efektif menolak nomor baru.
3. TV dan petugas menampilkan data antrean yang sama.
4. Listener audio terdaftar sekali dan pengumuman ter-scope ke zona/loket yang benar.
5. Normalisasi angka: 01 dibaca nol satu; 10 sepuluh; 12 dua belas; 20 dua puluh.
6. Kode 4K-10 dibaca empat ka sepuluh.
7. Uji coba Zona 4 menggunakan kalimat: “Nomor antrean empat ka nol satu, silakan menuju loket empat ka satu, [nama instansi].”
8. Zona lain mempertahankan format nama layanan.
9. Gunakan textContent/escaping aman untuk data dinamis.
10. Tambahkan test atau testable formatter untuk pelafalan dan scoping zona.

Berhenti setelah test lintas browser yang tersedia dan laporan. Jangan lanjut ke analitik.
```

### Gerbang Fase 6

- [ ] Tidak ada audio bocor antar-zona.
- [ ] Nomor dan kode dibaca benar.
- [ ] Status UI konsisten.
- [ ] Refresh tidak menggandakan suara.
- [ ] GO untuk Fase 7 diberikan.

---

# Fase 7 — Analitik antrean dan kehadiran

## Prompt 7A — definisi metrik dan review query

```text
Gunakan Prompt Aturan Umum dan hasil fase sebelumnya.

Review sumber data analitik tanpa mengubah kode.

Definisikan secara eksplisit:
- tiket diterbitkan;
- check-in online;
- menunggu, dipanggil, dilayani, selesai, batal;
- waktu tunggu;
- durasi pelayanan;
- jam puncak;
- perbandingan online/onsite;
- kehadiran petugas untuk instansi 5 dan 6 hari kerja.

Periksa query existing terhadap status, timezone, hari libur, tanggal masa depan, dan index. Rancang query/service agregasi, caching, filter, dan ekspor agar angka kartu, chart, tabel, dan Excel menggunakan sumber yang sama.

Berikan rencana test menggunakan dataset kecil dengan hasil hitungan manual. Berhenti sebelum implementasi.
```

## Prompt 7B — implementasi analitik

```text
Implementasikan Fase 7 sesuai definisi metrik yang disetujui.

Persyaratan:
1. Gunakan service agregasi bersama untuk dashboard dan ekspor.
2. Sediakan filter periode, zona, instansi, layanan, dan sumber sesuai kebutuhan.
3. Bandingkan instansi 5/6 hari berdasarkan hari kerja efektif, bukan tanggal kalender mentah.
4. Hari libur dan tanggal masa depan tidak dihitung sebagai absen.
5. Chart kategori bernama panjang memakai bar horizontal.
6. Chart.js memakai suggestedMax dinamis dan tension 0.1–0.2.
7. Data kosong harus tampil sebagai empty state, bukan error atau grafik palsu.
8. Cache query berat dengan key yang memasukkan seluruh filter.
9. Ekspor Excel harus sama dengan angka UI.
10. Tambahkan test hasil agregasi, filter, cache, empty state, dan ekspor.

Berhenti setelah test dan laporan performa query. Jangan melakukan deployment.
```

### Gerbang Fase 7

- [ ] Definisi metrik terdokumentasi.
- [ ] UI dan ekspor menghasilkan angka yang sama.
- [ ] Kalender 5/6 hari kerja benar.
- [ ] Query dashboard memiliki performa layak.
- [ ] GO untuk Fase 8 diberikan.

---

# Fase 8 — Pengujian, keamanan, dan hardening

## Prompt 8A — susun rencana pengujian

```text
Review aplikasi hasil Fase 1–7 dan susun test plan tanpa menjalankan test destruktif atau load test produksi.

Test plan wajib mencakup:
- concurrent generate nomor;
- concurrent check-in;
- concurrent panggil berikutnya;
- print success/fail;
- tutup/buka/auto-reopen;
- hari libur/cutoff;
- server restart;
- kehilangan jaringan;
- scoping audio antar-zona;
- polling banyak TV/kiosk;
- soak test 8 jam;
- backup dan restore;
- otorisasi route dan perlindungan data pribadi.

Tentukan environment testing, data dummy, volume, ambang lulus, monitoring, serta langkah penghentian jika ditemukan risiko. Berhenti sebelum menjalankan pengujian berat.
```

## Prompt 8B — jalankan hardening secara aman

```text
Jalankan Fase 8 hanya pada environment testing/staging yang terisolasi.

Sebelum mulai:
1. Buktikan APP_ENV dan database target bukan produksi.
2. Jangan lanjut bila database tidak berakhiran _testing atau tidak menggunakan SQLite memory/staging yang disetujui.
3. Catat baseline penggunaan CPU, RAM, query, dan error.

Jalankan test bertahap dari volume kecil. Jangan langsung menggunakan concurrency tinggi. Perbaiki hanya bug yang reproduktif dan masih dalam scope; review sebelum perubahan arsitektur.

Output:
- hasil setiap skenario;
- bug dan tingkat risiko;
- perubahan hardening;
- performa sebelum/sesudah;
- bukti tidak ada duplikasi/lost update;
- hasil soak test;
- hasil simulasi restore;
- rekomendasi GO/NO-GO deployment.
```

### Gerbang Fase 8

- [ ] Seluruh test kritis lulus.
- [ ] Restore backup berhasil.
- [ ] Tidak ada duplikasi akibat concurrency.
- [ ] Tidak ada kebocoran data atau audio lintas zona.
- [ ] GO deployment diberikan.

---

# Fase 9 — Deployment, monitoring, dan rollback

## Prompt 9A — review kesiapan deployment

```text
Review kesiapan deployment SiolaQ tanpa mengeksekusi deploy.

Periksa:
- branch dan commit yang akan dirilis;
- perubahan migration;
- composer.lock/package-lock;
- kebutuhan build asset;
- konfigurasi scheduler;
- Apache/MySQL auto-start;
- IP statis, DNS/subdomain, SSL, firewall;
- APP_ENV production dan APP_DEBUG false tanpa menampilkan isi rahasia;
- backup terakhir dan hasil restore test;
- ruang disk dan retensi log/backup;
- langkah smoke test;
- rollback kode dan database.

Jangan membaca atau menampilkan nilai secret .env. Berikan checklist GO/NO-GO dan urutan deploy yang sesuai kondisi server aktual. Berhenti sebelum deploy.
```

## Prompt 9B — deployment terkontrol

```text
Lakukan deployment hanya setelah pengguna memberi izin eksplisit dan checklist Fase 9A berstatus GO.

Aturan:
1. Catat commit saat ini dan target release.
2. Buat dan verifikasi backup database sebelum migration.
3. Jangan memasukkan .env atau backup ke Git.
4. Gunakan maintenance window bila migration berpotensi mengunci tabel.
5. Jalankan langkah deploy satu per satu; berhenti pada kegagalan pertama.
6. Setelah migration/build, jalankan optimize dan restart service hanya jika memang diperlukan.
7. Smoke test: login admin, kiosk generate, petugas panggil, TV/audio, tutup/buka, event/check-in, dashboard, dan scheduler.
8. Jika smoke test kritis gagal, lakukan rollback sesuai rencana yang telah disetujui. Jangan melakukan reset destruktif.

Laporkan hasil deploy, commit aktif, migration aktif, smoke test, dan kondisi backup. Jangan push atau mengubah branch lain tanpa izin.
```

### Gerbang Fase 9

- [ ] Backup terverifikasi.
- [ ] Deployment selesai tanpa error kritis.
- [ ] Smoke test seluruh jalur utama lulus.
- [ ] Scheduler dan monitoring aktif.
- [ ] Rollback point tercatat.

---

# Prompt pemeriksaan setelah setiap fase

```text
Lakukan review akhir terhadap perubahan fase ini. Jangan menambah fitur baru.

Periksa:
1. Apakah perubahan tetap dalam scope fase?
2. Apakah ada file pengguna yang tertimpa?
3. Apakah ada migration lama yang diubah?
4. Apakah ada query tanpa index atau N+1 baru?
5. Apakah ada race condition, lost update, atau celah idempotency?
6. Apakah authorization dan middleware tetap berlaku?
7. Apakah data pribadi muncul pada log, URL, TV, screenshot, fixture, atau Git?
8. Apakah .env, SQL backup, token, password, dan artefak akun terhindar dari Git?
9. Apakah test menggunakan database testing terisolasi?
10. Apakah rollback dan backward compatibility sudah dijelaskan?

Jalankan syntax check, formatter check, test terarah, git diff --check, dan secret scan pada staged diff. Laporkan temuan berdasarkan prioritas. Jangan commit atau push kecuali diminta.
```

# Prompt commit dan push setelah fase disetujui

```text
Fase ini sudah disetujui untuk disimpan ke Git.

Sebelum commit:
1. Tampilkan daftar file yang akan di-stage.
2. Pastikan hanya file dalam scope fase yang masuk.
3. Pastikan .env, backup SQL, spreadsheet akun, screenshot kredensial, token, password, private key, dan folder kerja lokal tidak masuk.
4. Jalankan git diff --cached --check dan scan secret pada staged diff.
5. Gunakan satu commit dengan pesan yang menjelaskan fase dan hasil utamanya.

Push hanya ke branch yang disebutkan pengguna. Jangan force push. Setelah push, laporkan branch, commit hash, dan konfirmasi file sensitif tidak ikut.
```

# Ringkasan urutan aman

```text
Fase 0: audit dan keputusan
  ↓
Fase 1: master data dan constraint
  ↓
Fase 2: kontrak queue onsite dan concurrency
  ↓
Fase 3: jadwal/cutoff/override satu sumber
  ↓
Fase 4: mesin antrean gabungan
  ↓
Fase 5: reservasi online dan check-in
  ↓
Fase 6: UI, kiosk, TV, dan TTS
  ↓
Fase 7: analitik dan ekspor
  ↓
Fase 8: hardening dan pengujian
  ↓
Fase 9: deployment terkontrol
```

Jangan melompati Fase 0–4 untuk langsung membuat pendaftaran online. Modul online baru aman ketika struktur data, status operasional, transaksi queue, dan algoritma panggil berikutnya sudah stabil.
