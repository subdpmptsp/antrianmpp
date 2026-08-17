<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TV Display - {{ $zoneName ?? 'Zona' }}</title>
    @vite(['resources/css/app.css'])
    <style>
        
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 50%, #1d4ed8 100%);
            min-height: 100vh;
            overflow: hidden;
        }
        
        .scrollable-area {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
        }
        
        .scrollable-area::-webkit-scrollbar {
            width: 8px;
        }
        
        .scrollable-area::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
        
        .scrollable-area::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 4px;
        }
        
        .scrollable-area::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .tv-container {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .queue-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(59, 130, 246, 0.2);
            transition: all 0.3s ease;
        }
        
        .queue-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .queue-number {
            font-size: clamp(4rem, 12vw, 8rem);
            font-weight: 900;
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.3);
        }
        
        .service-name {
            font-size: clamp(1.2rem, 3vw, 2rem);
            font-weight: 700;
        }
        
        .status-dot {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        .status-serving { background-color: #10b981; }
        .status-waiting { background-color: #f59e0b; }
        .status-available { background-color: #3b82f6; }
        .status-cancelled { background-color: #ef4444; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .announcement-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.5s ease-in;
        }
        
        .announcement-card {
            background: white;
            border-radius: 2rem;
            padding: 3rem;
            text-align: center;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        
        .loading {
            display: inline-block;
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="tv-container px-8 py-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-6">
                <img src="{{ asset('img/logopemkot_white.png') }}" alt="Logo" class="h-20 object-contain">
                <div>
                    <h1 class="text-white text-3xl font-bold">SIOLA MALL PELAYANAN PUBLIK</h1>
                    <p class="text-white/80 text-lg">Jl. Tunjungan No.1-3, Genteng, Kec. Genteng, Surabaya</p>
                </div>
            </div>
            <div class="text-right text-white">
                <div class="text-4xl font-bold" id="current-time"></div>
                <div class="text-lg" id="current-date"></div>
            </div>
        </div>
        
        <!-- Zone Title -->
        <div class="text-center mt-6">
            <h2 class="text-white text-5xl font-black uppercase tracking-wider" id="zone-title">
                {{ $zoneName ?? 'ZONA' }}
            </h2>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-8 py-6 scrollable-area">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8" id="queues-container">
            <div class="col-span-full flex justify-center items-center py-20">
                <div class="loading"></div>
                <span class="ml-4 text-white text-xl">Memuat data antrian...</span>
            </div>
        </div>
    </div>

    <!-- Announcement Overlay -->
    <div id="announcement-overlay" class="announcement-overlay hidden">
        <div class="announcement-card">
            <div class="text-8xl font-black text-blue-600 mb-6" id="announcement-number">A001</div>
            <div class="text-3xl font-bold text-gray-800 mb-4" id="announcement-service">Pengambilan Izin</div>
            <div class="text-xl text-gray-600 mb-2" id="announcement-counter">ZONA 1</div>
            <div class="text-lg text-gray-500" id="announcement-time">10:30:45</div>
        </div>
    </div>

    <script>
        let currentZone = '{{ $zoneId ?? 1 }}';
        let announcementQueue = [];
        let isAnnouncing = false;
        const announcementStorageKey = `tv:last-announcement:${currentZone}`;
        let lastAnnouncementId = sessionStorage.getItem(announcementStorageKey);

        function escapeHtml(value) {
            const element = document.createElement('div');
            element.textContent = String(value ?? '');
            return element.innerHTML;
        }
        
        // Update time
        function updateTime() {
            const now = new Date();
            document.getElementById('current-time').textContent = now.toLocaleTimeString('id-ID');
            document.getElementById('current-date').textContent = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }
        
        // Load queues data
        async function loadQueues() {
            try {
                const response = await fetch(`/api/tv-display/zone/${currentZone}/queues`);
                const data = await response.json();
                
                const container = document.getElementById('queues-container');
                container.innerHTML = '';
                
                if (data.queues && data.queues.length > 0) {
                    data.queues.forEach(queue => {
                        const queueCard = document.createElement('div');
                        queueCard.className = 'queue-card rounded-3xl p-8 text-center';
                        
                        let statusClass = 'status-available';
                        let statusText = 'Tersedia';

                        if (queue.status === 'serving') {
                            statusClass = 'status-serving';
                            statusText = 'Melayani';
                        } else if (queue.status === 'called') {
                            statusClass = 'status-waiting';
                            statusText = 'Dipanggil';
                        } else if (queue.status === 'canceled') {
                            statusClass = 'status-cancelled';
                            statusText = 'Dibatalkan';
                        }
                        
                        queueCard.innerHTML = `
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="font-bold text-gray-800 text-xl">${escapeHtml(queue.counter_name)}</h3>
                                <div class="flex items-center space-x-3">
                                    <div class="status-dot ${statusClass}"></div>
                                    <span class="text-sm font-medium text-gray-600">${statusText}</span>
                                </div>
                            </div>
                            
                            <div class="mb-8">
                                ${queue.queue_number ? `
                                    <div class="queue-number text-blue-600 mb-4">${escapeHtml(queue.queue_number)}</div>
                                    <div class="service-name text-gray-800 mb-4">${escapeHtml(queue.service_name)}</div>
                                    <div class="flex items-center justify-center text-gray-600 text-lg">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                        </svg>
                                        ${escapeHtml(queue.called_at || 'Belum dipanggil')}
                                    </div>
                                ` : `
                                    <div class="text-6xl text-gray-400 mb-4">⏸️</div>
                                    <div class="text-gray-600 text-xl mb-2">Belum ada panggilan</div>
                                    <div class="text-gray-500 text-lg">Menunggu antrian berikutnya</div>
                                `}
                            </div>
                            
                            <div class="w-full bg-gray-100 text-gray-700 font-bold py-4 px-6 rounded-xl text-lg">
                                Tampilan hanya-baca
                            </div>
                        `;
                        
                        container.appendChild(queueCard);
                    });
                } else {
                    container.innerHTML = `
                        <div class="col-span-full text-center text-white py-20">
                            <div class="text-6xl mb-4">📺</div>
                            <div class="text-2xl font-bold mb-2">Tidak ada data antrian</div>
                            <div class="text-lg opacity-80">Silakan tunggu data antrian tersedia</div>
                        </div>
                    `;
                }
            } catch (error) {
                console.error('Error loading queues:', error);
                const container = document.getElementById('queues-container');
                container.innerHTML = `
                    <div class="col-span-full text-center text-white py-20">
                        <div class="text-6xl mb-4">⚠️</div>
                        <div class="text-2xl font-bold mb-2">Error memuat data</div>
                        <div class="text-lg opacity-80">Silakan refresh halaman</div>
                    </div>
                `;
            }
        }
        
        // Play announcement
        function playAnnouncement(data) {
            if (isAnnouncing) {
                announcementQueue.push(data);
                return;
            }
            
            isAnnouncing = true;
            
            // Show popup
            const overlay = document.getElementById('announcement-overlay');
            document.getElementById('announcement-number').textContent = data.queueNumber;
            document.getElementById('announcement-service').textContent = data.serviceName;
            document.getElementById('announcement-counter').textContent = data.counterName;
            document.getElementById('announcement-time').textContent = data.calledAt;
            
            overlay.classList.remove('hidden');
            
            // Play audio
            // Format instansi - UPTSP dieja, yang lain tidak
            let instansiText = data.zona.toLowerCase();
            if (data.zona.toUpperCase() === 'UPTSP') {
                instansiText = 'U-P-T-S-P';
            }
            
            const announcementText = `nomor antrian ${data.queueNumber}, layanan ${data.serviceName.toLowerCase()}, menuju ke ${data.counterName}, ${instansiText}`;
            
            if (typeof responsiveVoice !== 'undefined') {
                responsiveVoice.speak(announcementText, 'Indonesian Female', {
                    rate: 0.8,
                    pitch: 1,
                    volume: 1
                });
            } else {
                // Fallback to speech synthesis
                const utterance = new SpeechSynthesisUtterance(announcementText);
                utterance.lang = 'id-ID';
                utterance.rate = 0.8;
                utterance.pitch = 1;
                utterance.volume = 1;
                speechSynthesis.speak(utterance);
            }
            
            // Hide popup after 6 seconds
            setTimeout(() => {
                overlay.classList.add('hidden');
                isAnnouncing = false;
                
                // Process next announcement
                if (announcementQueue.length > 0) {
                    const nextAnnouncement = announcementQueue.shift();
                    playAnnouncement(nextAnnouncement);
                }
            }, 6000);
        }

        async function checkForAnnouncements() {
            try {
                const params = new URLSearchParams({ zone_id: currentZone });
                if (lastAnnouncementId) {
                    params.set('after_id', lastAnnouncementId);
                }

                const response = await fetch(`/api/tv-display/latest-announcement?${params.toString()}`);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                if (data && data.announcementId) {
                    lastAnnouncementId = data.announcementId;
                    sessionStorage.setItem(announcementStorageKey, lastAnnouncementId);
                    playAnnouncement(data);
                }
            } catch (error) {
                console.error('Error checking announcements:', error);
            }
        }
        
        // Auto-refresh data
        function refreshData() {
            loadQueues();
        }
        
        // Initialize
        updateTime();
        setInterval(updateTime, 1000);
        
        refreshData();
        setInterval(refreshData, 3000); // Refresh every 3 seconds
        checkForAnnouncements();
        setInterval(checkForAnnouncements, 2000);
    </script>
</body>
</html>
