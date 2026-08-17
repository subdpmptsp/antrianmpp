# 🎵 Panduan Audio dari Link Eksternal

## 📋 **Overview**

Sistem antrian sekarang mendukung penggunaan audio dari link eksternal melalui API. Anda bisa menggunakan berbagai layanan Text-to-Speech (TTS) atau audio file langsung dari URL eksternal.

## 🚀 **Fitur yang Tersedia**

### ✅ **Layanan TTS yang Didukung:**
- **Google Text-to-Speech API**
- **ElevenLabs API**
- **Azure Cognitive Services**
- **Custom URL Audio**
- **Fallback ke Speech Synthesis Browser**

### ✅ **Fitur Manajemen:**
- Upload audio file
- Test audio langsung
- Konfigurasi melalui halaman admin
- Auto cleanup file lama
- Fallback otomatis jika API gagal

## 🔧 **Setup dan Konfigurasi**

### **1. Environment Variables (.env)**

```env
# Default Audio Service
AUDIO_DEFAULT_SERVICE=default

# Google Text-to-Speech
GOOGLE_TTS_API_KEY=your_google_api_key_here

# ElevenLabs
ELEVENLABS_API_KEY=your_elevenlabs_api_key_here
ELEVENLABS_VOICE_ID=pNInz6obpgDQGcFmaJgB

# Azure Cognitive Services
AZURE_TTS_API_KEY=your_azure_api_key_here
AZURE_TTS_REGION=eastus

# Custom Audio URL
CUSTOM_AUDIO_URL=https://example.com/audio?text={text}&queue={queueNumber}
```

### **2. Konfigurasi Audio (config/audio.php)**

```php
return [
    'default_service' => env('AUDIO_DEFAULT_SERVICE', 'default'),
    
    'google' => [
        'api_key' => env('GOOGLE_TTS_API_KEY'),
        'voice' => [
            'language_code' => 'id-ID',
            'name' => 'id-ID-Wavenet-A',
            'ssml_gender' => 'FEMALE'
        ]
    ],
    
    'elevenlabs' => [
        'api_key' => env('ELEVENLABS_API_KEY'),
        'voice_id' => env('ELEVENLABS_VOICE_ID', 'pNInz6obpgDQGcFmaJgB'),
        'model_id' => 'eleven_multilingual_v2'
    ],
    
    'custom' => [
        'url' => env('CUSTOM_AUDIO_URL'),
        'placeholders' => [
            '{text}' => 'Full announcement text',
            '{queueNumber}' => 'Queue number only',
            '{serviceName}' => 'Service name only',
            '{counterName}' => 'Counter name only',
            '{zona}' => 'Zone name only'
        ]
    ]
];
```

## 🎯 **Cara Penggunaan**

### **1. Melalui Halaman Admin**

1. Buka halaman **"Manajemen Audio"** di admin panel
2. Masukkan URL audio eksternal
3. Klik **"Test Audio"** untuk menguji
4. Klik **"Simpan Audio"** untuk menyimpan konfigurasi

### **2. Melalui API Endpoint**

```javascript
// Fetch audio dari API
fetch('/api/audio/announcement?queueNumber=1A003&serviceName=Pengambilan Izin&counterName=ZONA 1&zona=UPTSP')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Play audio dari URL yang dikembalikan
            const audio = new Audio(data.audioUrl);
            audio.play();
        }
    });
```

### **3. Upload Audio File**

```javascript
// Upload audio file
const formData = new FormData();
formData.append('audio', audioFile);
formData.append('name', 'custom_announcement.mp3');

fetch('/api/audio/upload', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {
    console.log('Audio uploaded:', data.audioUrl);
});
```

## 🔗 **Contoh URL Audio Eksternal**

### **1. Google Text-to-Speech**
```
https://texttospeech.googleapis.com/v1/text:synthesize?key=YOUR_API_KEY
```

### **2. ElevenLabs**
```
https://api.elevenlabs.io/v1/text-to-speech/VOICE_ID
```

### **3. Azure Cognitive Services**
```
https://eastus.tts.speech.microsoft.com/cognitiveservices/v1
```

### **4. Custom URL dengan Placeholder**
```
https://example.com/audio?text={text}&queue={queueNumber}&service={serviceName}
```

### **5. Audio File Langsung**
```
https://example.com/audio/announcement.mp3
```

## 📝 **Format Placeholder untuk Custom URL**

| Placeholder | Deskripsi | Contoh |
|-------------|-----------|---------|
| `{text}` | Teks lengkap pemanggilan | "Nomor antrian 1A003, layanan Pengambilan Izin..." |
| `{queueNumber}` | Nomor antrian saja | "1A003" |
| `{serviceName}` | Nama layanan saja | "Pengambilan Izin" |
| `{counterName}` | Nama loket saja | "ZONA 1" |
| `{zona}` | Nama zona saja | "UPTSP" |

## 🛠️ **API Endpoints**

### **GET /api/audio/announcement**
Mendapatkan URL audio untuk pemanggilan antrian.

**Parameters:**
- `queueNumber` (optional): Nomor antrian
- `serviceName` (optional): Nama layanan
- `counterName` (optional): Nama loket
- `zona` (optional): Nama zona

**Response:**
```json
{
    "success": true,
    "audioUrl": "https://example.com/audio.mp3",
    "queueNumber": "1A003",
    "serviceName": "Pengambilan Izin",
    "counterName": "ZONA 1",
    "zona": "UPTSP"
}
```

### **POST /api/audio/upload**
Upload file audio.

**Body (multipart/form-data):**
- `audio`: File audio (mp3, wav, ogg)
- `name`: Nama file

**Response:**
```json
{
    "success": true,
    "message": "Audio berhasil diupload",
    "audioUrl": "http://localhost:8000/storage/audio/filename.mp3",
    "filename": "filename.mp3"
}
```

### **GET /api/audio/list**
Mendapatkan daftar file audio yang tersedia.

**Response:**
```json
{
    "success": true,
    "audioList": [
        {
            "filename": "audio1.mp3",
            "url": "http://localhost:8000/storage/audio/audio1.mp3",
            "size": 1024000,
            "lastModified": 1696789123
        }
    ]
}
```

### **DELETE /api/audio/delete**
Menghapus file audio.

**Body:**
- `filename`: Nama file yang akan dihapus

**Response:**
```json
{
    "success": true,
    "message": "Audio berhasil dihapus"
}
```

## 🔄 **Alur Kerja Audio**

1. **User klik "Panggil Antrian Selanjutnya"**
2. **Sistem memanggil API `/api/audio/announcement`**
3. **API menentukan service audio yang digunakan**
4. **Generate audio URL berdasarkan konfigurasi**
5. **Frontend memutar audio dari URL yang dikembalikan**
6. **Jika audio gagal, fallback ke Speech Synthesis**

## 🎛️ **Manajemen Audio**

### **Halaman Admin: Manajemen Audio**
- Konfigurasi URL audio eksternal
- Test audio langsung
- Upload file audio
- Lihat daftar audio yang tersedia

### **Auto Cleanup**
- File audio otomatis dihapus setelah 7 hari
- Bisa dikonfigurasi di `config/audio.php`

## 🚨 **Troubleshooting**

### **Audio Tidak Berbunyi**
1. Cek konfigurasi API key
2. Test URL audio secara manual
3. Cek console browser untuk error
4. Pastikan fallback ke Speech Synthesis aktif

### **API Error**
1. Cek koneksi internet
2. Verifikasi API key
3. Cek quota API
4. Lihat log Laravel untuk detail error

### **File Upload Gagal**
1. Cek permission folder storage
2. Verifikasi format file (mp3, wav, ogg)
3. Cek ukuran file (max 10MB)

## 📊 **Monitoring dan Log**

- Log audio generation di `storage/logs/laravel.log`
- Monitor penggunaan API quota
- Track audio file storage usage

## 🔐 **Security**

- API key disimpan di environment variables
- File upload divalidasi format dan ukuran
- URL audio divalidasi sebelum digunakan
- Auto cleanup mencegah storage overflow

---

## 🎉 **Kesimpulan**

Sistem audio eksternal memberikan fleksibilitas untuk menggunakan berbagai layanan TTS dan audio file dari URL eksternal. Dengan fallback otomatis dan manajemen yang mudah, sistem tetap robust meskipun ada masalah dengan layanan eksternal.

**Silakan coba implementasi ini dan sesuaikan dengan kebutuhan Anda!** 🚀
