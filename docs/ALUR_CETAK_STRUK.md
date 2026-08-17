# Alur Sistem Cetak Struk Antrian

## Gambaran Umum
Sistem ini memungkinkan pengguna untuk memilih layanan dari "Unit Pelayanan Terpadu Satu Pintu (UPTSP)" dan mencetak struk antrian dengan desain yang telah ditentukan.

## Alur Lengkap

### 1. **Akses Halaman Cetak Antrian**
- Pengguna mengakses halaman "Kiosk Cetak Antrian" melalui menu sidebar
- Halaman menampilkan zona-zona yang tersedia (Zona 1, Zona 2, dll.)

### 2. **Pemilihan Zona**
- Pengguna memilih zona yang diinginkan (contoh: Zona 1)
- Sistem akan menampilkan layanan-layanan yang tersedia di zona tersebut

### 3. **Pemilihan Layanan**
- Pengguna melihat daftar layanan dalam "Unit Pelayanan Terpadu Satu Pintu (UPTSP)"
- Layanan yang tersedia meliputi:
  - Difabel
  - Konsultasi BPKAD
  - Konsultasi Dinkes
  - Konsultasi Dinsos
  - Konsultasi Disbudporapar
  - Konsultasi Dishub
  - Konsultasi Diskoperdag
  - Konsultasi Dispendik
  - Konsultasi Perijinan Non Berusaha
  - Konsultasi PTSP
  - Konsultasi Reklame
  - Lansia
  - Mandiri
  - Pengaduan
  - Pengambilan Izin

### 4. **Klik Tombol "Cetak Struk"**
- Setelah memilih layanan, pengguna mengklik tombol hijau "Cetak Struk"
- Sistem akan:
  - Generate nomor antrian otomatis berdasarkan prefix dan padding layanan
  - Simpan data antrian ke database
  - Menampilkan preview struk dalam window baru
  - Mencetak struk secara otomatis

### 5. **Format Struk yang Dicetak**
Struk akan menampilkan:
- **Logo**: Simbol ASCII art
- **Judul**: "MALL PELAYANAN PUBLIK"
- **Kota**: "KOTA SURABAYA"
- **Zona & Loket**: Sesuai zona dan instansi yang dipilih
- **Layanan**: Nama layanan yang dipilih
- **Nomor Antrian**: Nomor besar di tengah (contoh: 001)
- **Tanggal & Waktu**: Tanggal dan waktu saat struk dicetak

## Teknis Implementasi

### Backend (PHP/Laravel)
```php
// Method printStruk di QueueKiosk.php
public function printStruk($serviceId)
{
    // 1. Ambil data service dari database
    $service = Service::with('instansi')->find($serviceId);
    
    // 2. Generate nomor antrian
    $queueNumber = $this->generateQueueNumber($service);
    
    // 3. Simpan ke database
    $queue = \App\Models\Queue::create([
        'number' => $queueNumber,
        'service_id' => $service->id,
        'status' => 'waiting',
        'created_at' => now(),
    ]);
    
    // 4. Siapkan data struk
    $strukData = [
        'mall' => 'MALL PELAYANAN PUBLIK',
        'kota' => 'KOTA SURABAYA',
        'zona' => $this->counters[$this->selectedCounter]['name'],
        'loket' => $service->instansi?->nama_instansi,
        'layanan' => $service->name,
        'nomor' => $queueNumber,
        'tanggal' => now()->translatedFormat('j F Y'),
        'waktu' => now()->format('H:i:s'),
    ];
    
    // 5. Dispatch event untuk cetak
    $this->dispatch('print-struk', data: $strukData);
}
```

### Frontend (JavaScript)
```javascript
// Event listener untuk cetak struk
Livewire.on('print-struk', async (payload) => {
    const data = payload?.data || payload
    if (data) {
        await printStruk(data)
    }
})

// Fungsi cetak struk
async function printStruk(data) {
    // 1. Buat HTML struk dengan styling
    const strukHtml = `...`
    
    // 2. Buka window baru
    const printWindow = window.open('', '_blank', 'width=400,height=600');
    
    // 3. Tulis HTML ke window
    printWindow.document.write(strukHtml);
    
    // 4. Cetak otomatis
    setTimeout(() => {
        printWindow.print();
        printWindow.close();
    }, 500);
}
```

## Database Schema

### Tabel `queues`
- `id`: Primary key
- `number`: Nomor antrian (contoh: A001)
- `service_id`: Foreign key ke tabel services
- `status`: Status antrian (waiting, called, completed)
- `created_at`: Timestamp pembuatan

### Tabel `services`
- `id`: Primary key
- `name`: Nama layanan
- `prefix`: Prefix nomor antrian (contoh: A, B, C)
- `padding`: Jumlah digit padding (contoh: 3 untuk 001, 002, dst)
- `is_active`: Status aktif layanan

## Fitur Tambahan

### 1. **Generate Nomor Antrian Otomatis**
- Menggunakan prefix dan padding dari konfigurasi layanan
- Nomor akan reset setiap hari
- Format: [PREFIX][NOMOR] (contoh: A001, B002, C003)

### 2. **Preview Sebelum Cetak**
- Struk ditampilkan dalam window baru sebelum dicetak
- Pengguna dapat melihat preview dan memutuskan untuk mencetak atau tidak

### 3. **Notifikasi Sukses**
- Sistem menampilkan notifikasi ketika struk berhasil dicetak
- Menampilkan nomor antrian yang dihasilkan

### 4. **Penyimpanan Data**
- Setiap struk yang dicetak tersimpan di database
- Data dapat digunakan untuk monitoring dan laporan

## Cara Penggunaan

1. **Akses halaman**: Klik "Kiosk Cetak Antrian" di menu sidebar
2. **Pilih zona**: Klik zona yang diinginkan (contoh: Zona 1)
3. **Pilih layanan**: Klik salah satu layanan dari UPTSP
4. **Cetak struk**: Klik tombol hijau "Cetak Struk"
5. **Konfirmasi cetak**: Di window preview, klik "Print" untuk mencetak

## Troubleshooting

### Jika struk tidak muncul:
- Pastikan JavaScript enabled di browser
- Cek console browser untuk error
- Pastikan popup blocker tidak aktif

### Jika nomor antrian tidak sesuai:
- Cek konfigurasi prefix dan padding di tabel services
- Pastikan data layanan tersimpan dengan benar

### Jika cetak tidak berfungsi:
- Pastikan printer terhubung dan aktif
- Cek pengaturan printer default di browser
- Pastikan popup window tidak diblokir
