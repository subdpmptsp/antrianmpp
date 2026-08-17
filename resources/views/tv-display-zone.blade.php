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
        
        .tv-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .zone-header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .service-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .queue-number {
            font-size: clamp(3rem, 8vw, 6rem);
            font-weight: 900;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .service-name {
            font-size: clamp(1rem, 2.5vw, 1.5rem);
            font-weight: 600;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
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
        
        .announcement-popup {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
            animation: announcementSlide 0.5s ease-out;
        }
        
        @keyframes announcementSlide {
            from {
                opacity: 0;
                transform: translate(-50%, -60%);
            }
            to {
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .loading-spinner {
            border: 4px solid #f3f4f6;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="tv-container">
    <!-- Header -->
    <div class="zone-header px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="{{ asset('img/logopemkot_white.png') }}" alt="Logo" class="h-16 object-contain">
                <div>
                    <h1 class="text-white text-2xl font-bold">SIOLA MALL PELAYANAN PUBLIK</h1>
                    <p class="text-white/80 text-sm">Jl. Tunjungan No.1-3, Genteng, Kec. Genteng, Surabaya</p>
                </div>
            </div>
            <div class="text-right text-white">
                <div class="text-3xl font-bold" id="current-time"></div>
                <div class="text-sm" id="current-date"></div>
            </div>
        </div>
        
        <!-- Zone Title -->
        <div class="text-center mt-4">
            <h2 class="text-white text-4xl font-black uppercase tracking-wider" id="zone-title">
                {{ $zoneName ?? 'ZONA' }}
            </h2>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex h-screen">
        <!-- Sidebar - Service Status -->
        <div class="w-1/3 bg-white/10 backdrop-blur-sm p-6 overflow-y-auto">
            <h3 class="text-white text-xl font-bold mb-6">Status Layanan</h3>
            <div id="services-container" class="space-y-4">
                <div class="loading-spinner mx-auto"></div>
            </div>
        </div>

        <!-- Main Area - Queue Status -->
        <div class="flex-1 p-6 scrollable-area">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="queues-container">
                <div class="loading-spinner mx-auto col-span-full flex items-center justify-center"></div>
            </div>
        </div>
    </div>

    <!-- Announcement Popup -->
    <div id="announcement-popup" class="announcement-popup hidden">
        <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-2xl w-full mx-4 text-center">
            <div class="text-6xl font-black text-blue-600 mb-4" id="announcement-number">A001</div>
            <div class="text-2xl font-bold text-gray-800 mb-2" id="announcement-service">Pengambilan Izin</div>
            <div class="text-lg text-gray-600" id="announcement-counter">ZONA 1</div>
            <div class="text-sm text-gray-500 mt-4" id="announcement-time">10:30:45</div>
        </div>
    </div>

    <script>
        let currentZone = '{{ $zoneId ?? 1 }}';
        let announcementQueue = [];
        let isAnnouncing = false;
        
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
        
        // Load services data
        async function loadServices() {
            try {
                const response = await fetch(`/api/tv-display/zone/${currentZone}/services`);
                const data = await response.json();
                
                const container = document.getElementById('services-container');
                container.innerHTML = '';
                
                data.services.forEach(service => {
                    const serviceCard = document.createElement('div');
                    serviceCard.className = 'service-card rounded-xl p-4 fade-in';
                    serviceCard.innerHTML = `
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-800 text-sm">${service.name}</h4>
                            <div class="status-indicator ${service.status === 'serving' ? 'status-serving' : 
                                service.status === 'waiting' ? 'status-waiting' : 'status-available'}"></div>
                        </div>
                        <div class="text-xs text-gray-600">
                            <div>Antrian Berikutnya: ${service.next_queue || 'Tidak ada antrian'}</div>
                            <div>• ${service.active_counters}/${service.total_counters} Loket Aktif</div>
                        </div>
                    `;
                    container.appendChild(serviceCard);
                });
            } catch (error) {
                console.error('Error loading services:', error);
            }
        }
        
        // Load queues data
        async function loadQueues() {
            try {
                const response = await fetch(`/api/tv-display/zone/${currentZone}/queues`);
                const data = await response.json();
                
                const container = document.getElementById('queues-container');
                container.innerHTML = '';
                
                data.queues.forEach(queue => {
                    const queueCard = document.createElement('div');
                    queueCard.className = 'service-card rounded-2xl p-6 text-center fade-in';
                    
                    let statusClass = 'status-available';
                    let statusText = 'Tersedia';
                    let buttonClass = 'bg-blue-500 hover:bg-blue-600';
                    let buttonText = 'Dilayani';
                    
                    if (queue.status === 'serving') {
                        statusClass = 'status-serving';
                        statusText = 'Melayani';
                        buttonClass = 'bg-green-500 hover:bg-green-600';
                    } else if (queue.status === 'called') {
                        statusClass = 'status-waiting';
                        statusText = 'Dipanggil';
                        buttonClass = 'bg-yellow-500 hover:bg-yellow-600';
                    } else if (queue.status === 'cancelled') {
                        statusClass = 'status-cancelled';
                        statusText = 'Dibatalkan';
                        buttonClass = 'bg-red-500 hover:bg-red-600';
                    }
                    
                    queueCard.innerHTML = `
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-bold text-gray-800 text-lg">${queue.counter_name}</h3>
                            <div class="flex items-center space-x-2">
                                <div class="status-indicator ${statusClass}"></div>
                                <span class="text-sm font-medium text-gray-600">${statusText}</span>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            ${queue.queue_number ? `
                                <div class="queue-number text-blue-600 mb-2">${queue.queue_number}</div>
                                <div class="service-name text-gray-800 mb-2">${queue.service_name}</div>
                                <div class="flex items-center justify-center text-gray-600 text-sm">
                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
                                    </svg>
                                    ${queue.called_at || 'Belum dipanggil'}
                                </div>
                            ` : `
                                <div class="text-4xl text-gray-400 mb-2">⏸️</div>
                                <div class="text-gray-600 text-lg">Belum ada panggilan</div>
                                <div class="text-gray-500 text-sm">Menunggu antrian berikutnya</div>
                            `}
                        </div>
                        
                        <button class="w-full ${buttonClass} text-white font-bold py-3 px-4 rounded-lg transition-colors">
                            ${buttonText}
                        </button>
                    `;
                    
                    container.appendChild(queueCard);
                });
            } catch (error) {
                console.error('Error loading queues:', error);
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
            const popup = document.getElementById('announcement-popup');
            document.getElementById('announcement-number').textContent = data.queueNumber;
            document.getElementById('announcement-service').textContent = data.serviceName;
            document.getElementById('announcement-counter').textContent = data.counterName;
            document.getElementById('announcement-time').textContent = data.calledAt;
            
            popup.classList.remove('hidden');
            
            // Play audio
            // Format instansi - UPTSP dieja, yang lain tidak
            let instansiText = data.zona.toLowerCase();
            if (data.zona.toUpperCase() === 'UPTSP') {
                instansiText = 'U-P-T-S-P';
            }
            
            const announcementText = `nomor antrian ${data.queueNumber}, layanan ${data.serviceName.toLowerCase()}, menuju ke loket ${data.servicePrefix || 'A'}, ${instansiText}`;
            
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
            
            // Hide popup after 5 seconds
            setTimeout(() => {
                popup.classList.add('hidden');
                isAnnouncing = false;
                
                // Process next announcement
                if (announcementQueue.length > 0) {
                    const nextAnnouncement = announcementQueue.shift();
                    playAnnouncement(nextAnnouncement);
                }
            }, 5000);
        }
        
        // Listen for announcements
        window.addEventListener('announce-queue', (event) => {
            playAnnouncement(event.detail);
        });
        
        // Auto-refresh data
        function refreshData() {
            loadServices();
            loadQueues();
        }
        
        // Initialize
        updateTime();
        setInterval(updateTime, 1000);
        
        refreshData();
        setInterval(refreshData, 5000); // Refresh every 5 seconds
        
        // Listen for Livewire events
        window.addEventListener('announce-queue', (event) => {
            playAnnouncement(event.detail);
        });
    </script>
</body>
</html>
