# Role dan matriks akses

Nilai role yang disimpan di database tetap stabil untuk menjaga kompatibilitas:

- `admin` — label antarmuka **Admin**.
- `operator` — label antarmuka **Petugas Layanan**.

`operator` adalah nama teknis lama, bukan nama yang perlu ditampilkan kepada pengguna. Jangan membuat variasi baru seperti `petugas`, `petugas_layanan`, atau `administrator` tanpa migrasi terencana.

| Kemampuan | Admin | Petugas Layanan | Mesin Antrian | TV Layanan |
|---|:---:|:---:|:---:|:---:|
| Master data, user, setting, export | Ya | Tidak | Tidak | Tidak |
| Panggil/layani/selesaikan antrean | Ya | Hanya loket tugas | Tidak | Tidak |
| Membuat tiket | Tidak melalui dashboard | Tidak | Ya, token perangkat | Tidak |
| Melihat dan menyuarakan panggilan | Ya | Ya | Tidak | Ya, read-only dan token zona |

Otorisasi server wajib tetap berlaku meskipun menu disembunyikan. Pengujian utama terdapat pada `RoleNavigationAccessTest`, `DashboardAccessTest`, `DeviceAuthorizationTest`, dan `ProtectedMutationRoutesTest`.
