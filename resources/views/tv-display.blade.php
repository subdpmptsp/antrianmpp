<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mppBranding['name'] }} — TV Pemanggilan Antrean</title>
    @vite(['resources/css/app.css'])
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            overflow: hidden;
        }
        
        .announcement-card {
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        .fade-out {
            animation: fadeOut 0.5s ease-out forwards;
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: scale(1);
            }
            to {
                opacity: 0;
                transform: scale(0.95);
            }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div id="mainDisplay" class="text-center">
        <!-- Default state -->
        <div id="defaultState" class="announcement-card">
            <div class="bg-white rounded-3xl shadow-2xl p-12 max-w-2xl mx-auto">
                <div class="w-32 h-32 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-8 pulse-animation">
                    <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 14.142M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                    </svg>
                </div>
                <h1 class="text-4xl font-bold text-gray-800 mb-4">SISTEM ANTRIAN</h1>
                <h2 class="text-2xl font-semibold text-gray-600 mb-2">MALL PELAYANAN PUBLIK</h2>
                <h3 class="text-xl text-gray-500">KOTA SURABAYA</h3>
                <div class="mt-8 text-lg text-gray-400">
                    Menunggu pemanggilan antrian...
                </div>
            </div>
        </div>
        
        <!-- Announcement state -->
        <div id="announcementState" class="announcement-card hidden">
            <div class="bg-white rounded-3xl shadow-2xl p-12 max-w-4xl mx-auto">
                <div class="w-24 h-24 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-8 pulse-animation">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 14.142M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                    </svg>
                </div>
                
                <h2 class="text-3xl font-bold text-gray-800 mb-6">PEMANGGILAN ANTRIAN</h2>
                
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-2xl p-8 mb-8">
                    <div class="text-6xl font-bold text-white mb-4" id="announcedQueueNumber">-</div>
                    <div class="text-2xl text-blue-100" id="announcedService">-</div>
                </div>
                
                <div class="text-xl text-gray-700 mb-4">
                    <span class="font-semibold">Silakan menuju ke:</span>
                </div>
                <div class="text-2xl font-bold text-gray-800 mb-2" id="announcedCounter">-</div>
                <div class="text-lg text-gray-600 mb-6" id="announcedZona">-</div>
                
                <div class="text-sm text-gray-500" id="announcedTime">-</div>
            </div>
        </div>
    </div>

    <!-- Audio untuk pemanggilan -->
    <audio id="announcementAudio" preload="auto">
        <source src="/sounds/announcement.mp3" type="audio/mpeg">
        <source src="/sounds/announcement.wav" type="audio/wav">
    </audio>

    <script>
        const defaultState = document.getElementById('defaultState');
        const announcementState = document.getElementById('announcementState');
        const audio = document.getElementById('announcementAudio');
        
        let lastAnnouncementId = null;

        // Simulasi koneksi ke server (bisa diganti dengan WebSocket atau polling)
        function checkForAnnouncements() {
            // Polling ke server untuk mendapatkan announcement terbaru
            const url = lastAnnouncementId
                ? `/api/announcements/latest?after_id=${encodeURIComponent(lastAnnouncementId)}`
                : '/api/announcements/latest';
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data && data.announcementId) {
                        lastAnnouncementId = data.announcementId;
                        showAnnouncement(data);
                    }
                })
                .catch(error => {
                    console.log('No announcements or error:', error);
                });
        }
        
        function showAnnouncement(data) {
            console.log('Showing announcement:', data);
            
            // Update data
            document.getElementById('announcedQueueNumber').textContent = data.queueNumber;
            document.getElementById('announcedService').textContent = data.serviceName;
            document.getElementById('announcedCounter').textContent = data.counterName;
            document.getElementById('announcedZona').textContent = data.zona;
            document.getElementById('announcedTime').textContent = `Dipanggil pada: ${data.calledAt}`;
            
            // Hide default, show announcement
            defaultState.classList.add('hidden');
            announcementState.classList.remove('hidden');
            
            // Play sound
            playAnnouncementSound(data);
            
            // Auto-hide after 10 seconds
            setTimeout(() => {
                hideAnnouncement();
            }, 10000);
        }
        
        function hideAnnouncement() {
            announcementState.classList.add('fade-out');
            setTimeout(() => {
                announcementState.classList.add('hidden');
                announcementState.classList.remove('fade-out');
                defaultState.classList.remove('hidden');
            }, 500);
        }
        
        function playAnnouncementSound(data) {
            try {
                // Coba putar audio file jika ada
                audio.play().catch(e => {
                    console.log('Audio play failed, using speech synthesis:', e);
                    speakAnnouncement(data);
                });
            } catch (e) {
                console.log('Audio not available, using speech synthesis:', e);
                speakAnnouncement(data);
            }
        }
        
        function speakAnnouncement(data) {
            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance();
                utterance.text = `Nomor antrian ${data.queueNumber}, silakan menuju ke ${data.counterName}`;
                utterance.lang = 'id-ID';
                utterance.rate = 0.8;
                utterance.pitch = 1;
                utterance.volume = 1;
                
                // Coba gunakan voice Indonesia jika tersedia
                const voices = speechSynthesis.getVoices();
                const indonesianVoice = voices.find(voice => 
                    voice.lang.includes('id') || voice.lang.includes('ID')
                );
                if (indonesianVoice) {
                    utterance.voice = indonesianVoice;
                }
                
                speechSynthesis.speak(utterance);
            }
        }
        
        // Polling setiap 2 detik
        setInterval(checkForAnnouncements, 2000);
        
        // Initial check
        checkForAnnouncements();
        
        // Test function (untuk testing manual)
        window.testAnnouncement = function() {
            showAnnouncement({
                queueNumber: 'A001',
                serviceName: 'Pengambilan Izin',
                counterName: 'Loket 1',
                zona: 'Zona 1',
                calledAt: new Date().toLocaleTimeString('id-ID')
            });
        };
    </script>
</body>
</html>
