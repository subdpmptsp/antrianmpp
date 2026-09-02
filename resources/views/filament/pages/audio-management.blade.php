<x-filament-panels::page>
    
    <div class="space-y-6">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 14.142M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Manajemen Audio</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Kelola audio untuk pemanggilan antrian</p>
                </div>
            </div>
        </div>

        <!-- Current Audio Status -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Audio Saat Ini</h3>
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">URL Audio:</p>
                        <p class="font-mono text-sm text-gray-900 dark:text-white break-all">{{ $currentAudioUrl }}</p>
                    </div>
                    <button onclick="testCurrentAudio()" 
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m6-6a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Test Audio
                    </button>
                </div>
            </div>
        </div>

        <!-- Pengaturan TTS -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-start justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Suara Pemanggilan TTS</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Atur dan dengarkan preview lebih dulu. Pengaturan baru dipakai untuk pemanggilan setelah disimpan.</p>
                </div>
                <span class="shrink-0 px-3 py-1 text-xs font-semibold text-blue-700 bg-blue-50 rounded-full">TTS Browser</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="tts-voice" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Pilihan Suara</label>
                    <select id="tts-voice" wire:model="ttsVoice" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                        <option value="auto">Otomatis — suara Indonesia tersedia</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Suara mengikuti perangkat/browser.</p>
                </div>
                <div>
                    <label for="tts-rate" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Kecepatan (0,5–1,5)</label>
                    <input id="tts-rate" type="number" min="0.5" max="1.5" step="0.1" wire:model="ttsRate" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label for="tts-pitch" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nada (0,5–1,5)</label>
                    <input id="tts-pitch" type="number" min="0.5" max="1.5" step="0.1" wire:model="ttsPitch" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label for="tts-volume" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Volume (0–1)</label>
                    <input id="tts-volume" type="number" min="0" max="1" step="0.1" wire:model="ttsVolume" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white">
                </div>
            </div>

            <div class="mt-5">
                <label for="tts-preview" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teks Preview</label>
                <textarea id="tts-preview" wire:model="ttsPreviewText" rows="3" maxlength="500" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white"></textarea>
                <p class="text-xs text-gray-500 mt-1">Contoh: “Nomor antrean A, nol empat dua. Silakan menuju loket tiga.”</p>
            </div>

            <button type="button" wire:click="testTts" class="mt-5 px-5 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">▶ Putar Preview TTS</button>
        </div>

        <!-- Audio Configuration Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Konfigurasi Audio</h3>
            
            <form wire:submit="saveAudio" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="audio-url-input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            URL Audio Eksternal
                        </label>
                        <input type="url" 
                            id="audio-url-input"
                            name="audio-url-input"
                            wire:model="audioUrl"
                            placeholder="https://example.com/audio.mp3"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                            aria-label="URL Audio Eksternal">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Masukkan URL audio dari link eksternal
                        </p>
                    </div>
                    
                    <div>
                        <label for="audio-name-input" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Nama Audio
                        </label>
                        <input type="text" 
                            id="audio-name-input"
                            name="audio-name-input"
                            wire:model="audioName"
                            placeholder="Audio Pemanggilan Antrian"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                            aria-label="Nama Audio">
                    </div>
                </div>
                
                <div>
                    <label for="audio-description-textarea" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Deskripsi
                    </label>
                    <textarea id="audio-description-textarea"
                        name="audio-description-textarea"
                        wire:model="audioDescription"
                        rows="3"
                        placeholder="Deskripsi audio..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        aria-label="Deskripsi"></textarea>
                </div>
                
                <div>
                    <label for="audio-type-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Tipe Audio
                    </label>
                    <select id="audio-type-select"
                        name="audio-type-select"
                        wire:model="audioType"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white"
                        aria-label="Tipe Audio">
                        <option value="announcement">Audio Pemanggilan</option>
                        <option value="background">Audio Background</option>
                        <option value="notification">Audio Notifikasi</option>
                        <option value="responsivevoice">ResponsiveVoice TTS</option>
                    </select>
                </div>
                
                <div class="flex space-x-4">
                    <button type="button" 
                        wire:click="testAudio"
                        class="px-6 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h1m4 0h1m6-6a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Test Audio
                    </button>
                    
                    <button type="submit"
                        class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Simpan Audio
                    </button>
                </div>
            </form>
        </div>

        <!-- Audio Examples -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Contoh URL Audio</h3>
            <div class="space-y-3">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Google Text-to-Speech API:</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-mono break-all">
                        https://texttospeech.googleapis.com/v1/text:synthesize?key=YOUR_API_KEY
                    </p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">ElevenLabs API:</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-mono break-all">
                        https://api.elevenlabs.io/v1/text-to-speech/VOICE_ID
                    </p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Audio File Langsung:</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-mono break-all">
                        https://example.com/audio/announcement.mp3
                    </p>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                    <p class="text-sm font-medium text-gray-900 dark:text-white">ResponsiveVoice (Recommended):</p>
                    <p class="text-xs text-gray-600 dark:text-gray-400 font-mono break-all">
                        https://responsivevoice.org/ - Perfect for queue management systems
                    </p>
                    <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                        ✅ Free for non-commercial use | ✅ 51 languages | ✅ Real-time TTS
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function testCurrentAudio() {
            const audioUrl = '{{ $currentAudioUrl }}';
            console.log('Testing audio:', audioUrl);
            
            const audio = new Audio(audioUrl);
            audio.play().catch(error => {
                console.error('Audio test failed:', error);
                alert('Audio test gagal: ' + error.message);
            });
        }
        
        // Test audio dari Livewire
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('test-audio', (url) => {
                console.log('Testing audio from Livewire:', url);
                const audio = new Audio(url);
                audio.play().catch(error => {
                    console.error('Audio test failed:', error);
                    alert('Audio test gagal: ' + error.message);
                });
            });

            Livewire.on('test-tts', ({ text, voice, rate, pitch, volume }) => {
                if (!('speechSynthesis' in window)) {
                    alert('Browser ini belum mendukung TTS.')
                    return
                }

                speechSynthesis.cancel()
                const speechText = String(text)
                    .replace(/\bIKD\b/gi, 'I K D')
                    .replace(/\bYOB\b/gi, 'Y O B')
                const utterance = new SpeechSynthesisUtterance(speechText)
                utterance.lang = 'id-ID'
                utterance.rate = Number(rate)
                utterance.pitch = Number(pitch)
                utterance.volume = Number(volume)

                const voices = speechSynthesis.getVoices()
                const configuredVoice = voice !== 'auto' ? voices.find((item) => item.name === voice) : null
                utterance.voice = configuredVoice || voices.find((item) => item.lang?.toLowerCase().startsWith('id')) || null
                speechSynthesis.speak(utterance)
            })
        });
    </script>
</x-filament-panels::page>
