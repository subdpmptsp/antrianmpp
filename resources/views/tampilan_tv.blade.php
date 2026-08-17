<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIOLA MALL PELAYANAN PUBLIK - TV Display</title>
    @vite(['resources/css/app.css'])
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            overflow: hidden;
            height: 100vh;
        }
        
        /* TV Optimized Font Sizes */
        .tv-title { font-size: clamp(2.5rem, 6vw, 8rem); }
        .tv-subtitle { font-size: clamp(1.5rem, 4vw, 5rem); }
        .tv-queue-number { font-size: clamp(4rem, 12vw, 20rem); }
        .tv-service-name { font-size: clamp(1.2rem, 3vw, 4rem); }
        .tv-counter-name { font-size: clamp(1.5rem, 4vw, 6rem); }
        .tv-time { font-size: clamp(1.5rem, 4vw, 5rem); }
        .tv-date { font-size: clamp(1rem, 2.5vw, 3rem); }
        
        .service-card {
            transition: all 0.3s ease;
            border-left: 6px solid transparent;
        }
        
        .service-card.active {
            border-left-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
            transform: scale(1.02);
        }
        
        .service-card.waiting {
            border-left-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }
        
        .queue-card {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .queue-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s;
        }
        
        .queue-card:hover::before {
            left: 100%;
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            }
            50% { 
                transform: scale(1.05);
                box-shadow: 0 0 0 30px rgba(16, 185, 129, 0);
            }
        }
        
        .blink {
            animation: blink 1.5s infinite;
        }
        
        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }
        
        .slide-in {
            animation: slideIn 0.6s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-100px) scale(0.8);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .status-indicator {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 12px;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
        }
        
        .status-serving {
            background-color: #10b981;
            animation: pulse 2s infinite;
        }
        
        .status-available {
            background-color: #f59e0b;
        }
        
        .status-busy {
            background-color: #ef4444;
        }
        
        .announcement-card {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 50%, #1d4ed8 100%);
            box-shadow: 0 35px 60px -12px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }
        
        .announcement-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .service-icon {
            width: 32px;
            height: 32px;
            opacity: 0.8;
        }
        
        .time-display {
            font-variant-numeric: tabular-nums;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .glow-text {
            text-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }
        
        /* Responsive adjustments for different TV sizes */
        @media (min-width: 1920px) {
            .tv-queue-number { font-size: 20rem; }
            .tv-title { font-size: 8rem; }
        }
        
        @media (max-width: 1366px) {
            .tv-queue-number { font-size: 12rem; }
            .tv-title { font-size: 5rem; }
        }
        
        @media (max-width: 1024px) {
            .tv-queue-number { font-size: 8rem; }
            .tv-title { font-size: 3rem; }
        }
    </style>
</head>
<body class="h-screen flex flex-col">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-700 text-white py-6 px-8 shadow-2xl">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="tv-title font-black tracking-wide glow-text">SIOLA MALL PELAYANAN PUBLIK</h1>
                <p class="tv-subtitle font-semibold mt-2 text-blue-100">KOTA SURABAYA</p>
                <p class="text-xl mt-2 text-blue-200">Jl. Tunjungan No.1-3, Genteng, Kec. Genteng, Surabaya, Jawa Timur 60275</p>
            </div>
            <div class="text-right">
                <div class="tv-time font-bold time-display" id="currentTime"></div>
                <div class="tv-date text-blue-200" id="currentDate"></div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Left Sidebar - Service Status -->
        <div class="w-1/4 bg-white border-r-4 border-gray-300 p-6 overflow-y-auto">
            <h2 class="text-3xl font-bold text-gray-800 mb-8 text-center bg-gradient-to-r from-blue-100 to-blue-200 py-4 rounded-xl shadow-lg">
                STATUS LAYANAN
            </h2>
            
            <div class="space-y-4" id="serviceList">
                <!-- Service items will be populated by JavaScript -->
            </div>
        </div>

        <!-- Main Display Area -->
        <div class="flex-1 flex flex-col">
            <!-- Current Announcement -->
            <div id="announcementArea" class="hidden">
                <div class="announcement-card text-white p-12 m-6 rounded-3xl text-center slide-in relative z-10">
                    <div class="flex items-center justify-center mb-8">
                        <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-6 pulse-animation">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 14.142M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                            </svg>
                        </div>
                        <h3 class="text-5xl font-bold">PEMANGGILAN ANTRIAN</h3>
                    </div>
                    
                    <div class="bg-white bg-opacity-20 rounded-2xl p-8 mb-8 backdrop-blur-sm">
                        <div class="tv-queue-number font-black mb-6 text-yellow-300" id="announcedNumber">-</div>
                        <div class="text-4xl font-semibold" id="announcedService">-</div>
                    </div>
                    
                    <div class="text-3xl mb-4">
                        <span class="font-semibold">Silakan menuju ke:</span>
                    </div>
                    <div class="tv-counter-name font-bold mb-4" id="announcedCounter">-</div>
                    <div class="text-2xl opacity-90" id="announcedZone">-</div>
                </div>
            </div>

            <!-- Queue Status Grid -->
            <div id="queueGrid" class="flex-1 p-8">
                <div class="grid grid-cols-3 gap-8 h-full">
                    <!-- Serving Cards -->
                    <div class="queue-card bg-white rounded-3xl shadow-2xl border-4 border-green-300 p-8 flex flex-col justify-center items-center text-center">
                        <div class="flex items-center mb-6">
                            <div class="status-indicator status-serving"></div>
                            <span class="text-2xl font-bold text-green-800">MELAYANI</span>
                        </div>
                        <div class="tv-queue-number text-green-600" id="serving1">-</div>
                        <div class="tv-service-name font-medium text-gray-700 mt-4" id="servingService1">-</div>
                    </div>
                    
                    <div class="queue-card bg-white rounded-3xl shadow-2xl border-4 border-green-300 p-8 flex flex-col justify-center items-center text-center">
                        <div class="flex items-center mb-6">
                            <div class="status-indicator status-serving"></div>
                            <span class="text-2xl font-bold text-green-800">MELAYANI</span>
                        </div>
                        <div class="tv-queue-number text-green-600" id="serving2">-</div>
                        <div class="tv-service-name font-medium text-gray-700 mt-4" id="servingService2">-</div>
                    </div>
                    
                    <div class="queue-card bg-white rounded-3xl shadow-2xl border-4 border-green-300 p-8 flex flex-col justify-center items-center text-center">
                        <div class="flex items-center mb-6">
                            <div class="status-indicator status-serving"></div>
                            <span class="text-2xl font-bold text-green-800">MELAYANI</span>
                        </div>
                        <div class="tv-queue-number text-green-600" id="serving3">-</div>
                        <div class="tv-service-name font-medium text-gray-700 mt-4" id="servingService3">-</div>
                    </div>
                    
                    <!-- Available Cards -->
                    <div class="queue-card bg-white rounded-3xl shadow-2xl border-4 border-yellow-300 p-8 flex flex-col justify-center items-center text-center">
                        <div class="flex items-center mb-6">
                            <div class="status-indicator status-available"></div>
                            <span class="text-2xl font-bold text-yellow-800">TERSEDIA</span>
                        </div>
                        <div class="text-8xl text-gray-400 mb-4">-</div>
                        <div class="text-xl text-gray-500">Belum ada panggilan</div>
                        <div class="text-lg text-gray-400 mt-2">Menunggu antrian berikutnya</div>
                    </div>
                    
                    <div class="queue-card bg-white rounded-3xl shadow-2xl border-4 border-yellow-300 p-8 flex flex-col justify-center items-center text-center">
                        <div class="flex items-center mb-6">
                            <div class="status-indicator status-available"></div>
                            <span class="text-2xl font-bold text-yellow-800">TERSEDIA</span>
                        </div>
                        <div class="text-8xl text-gray-400 mb-4">-</div>
                        <div class="text-xl text-gray-500">Belum ada panggilan</div>
                        <div class="text-lg text-gray-400 mt-2">Menunggu antrian berikutnya</div>
                    </div>
                    
                    <div class="queue-card bg-white rounded-3xl shadow-2xl border-4 border-yellow-300 p-8 flex flex-col justify-center items-center text-center">
                        <div class="flex items-center mb-6">
                            <div class="status-indicator status-available"></div>
                            <span class="text-2xl font-bold text-yellow-800">TERSEDIA</span>
                        </div>
                        <div class="text-8xl text-gray-400 mb-4">-</div>
                        <div class="text-xl text-gray-500">Belum ada panggilan</div>
                        <div class="text-lg text-gray-400 mt-2">Menunggu antrian berikutnya</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Audio for announcements -->
    <audio id="announcementAudio" preload="auto">
        <source src="/sounds/opening.mp3" type="audio/mpeg">
        <source src="/sounds/opening.wav" type="audio/wav">
    </audio>

    <script>
        // Update time and date
        function updateDateTime() {
            const now = new Date();
            const timeOptions = { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: false 
            };
            const dateOptions = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            };
            
            document.getElementById('currentTime').textContent = now.toLocaleTimeString('id-ID', timeOptions);
            document.getElementById('currentDate').textContent = now.toLocaleDateString('id-ID', dateOptions);
        }

        // Global data storage
        let services = [];
        let servingQueues = [];
        let availableCounters = [];
        let lastAnnouncementId = null;

        // Fetch data from API
        async function fetchQueueStatus() {
            try {
                const response = await fetch('/api/tv-display/queue-status');
                const data = await response.json();
                
                services = data.services || [];
                servingQueues = data.servingQueues || [];
                availableCounters = data.availableCounters || [];
                
                renderServiceList();
                renderQueueGrid();
            } catch (error) {
                console.error('Error fetching queue status:', error);
                // Fallback data jika API tidak tersedia
                services = [
                    { name: 'Pengambilan Izin', status: 'available', nextQueue: 'A001', activeCounters: 1, totalCounters: 1 },
                    { name: 'Konsultasi PTSP', status: 'available', nextQueue: 'B001', activeCounters: 1, totalCounters: 1 },
                    { name: 'Konsultasi Diskoperdag', status: 'available', nextQueue: 'C001', activeCounters: 1, totalCounters: 1 },
                    { name: 'Konsultasi BPKAD', status: 'available', nextQueue: 'D001', activeCounters: 1, totalCounters: 1 },
                    { name: 'Konsultasi Dishub', status: 'available', nextQueue: 'E001', activeCounters: 1, totalCounters: 1 },
                    { name: 'Konsultasi Disbudporapar', status: 'available', nextQueue: 'F001', activeCounters: 1, totalCounters: 1 },
                    { name: 'Konsultasi Dispendik', status: 'available', nextQueue: 'G001', activeCounters: 1, totalCounters: 1 },
                    { name: 'Konsultasi Dinsos', status: 'available', nextQueue: 'H001', activeCounters: 1, totalCounters: 1 }
                ];
                renderServiceList();
            }
        }

        // Render service list
        function renderServiceList() {
            const serviceList = document.getElementById('serviceList');
            serviceList.innerHTML = services.map(service => `
                <div class="service-card ${service.status} bg-white rounded-xl p-5 shadow-lg">
                    <div class="flex items-center mb-3">
                        <svg class="service-icon text-gray-600 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-bold text-gray-800 text-lg">${service.name}</h3>
                            <p class="text-sm text-gray-600">Antrian Berikutnya</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-blue-600">${service.nextQueue || '-'}</p>
                        <p class="text-sm text-gray-500">• ${service.activeCounters}/${service.totalCounters} Loket Aktif</p>
                    </div>
                </div>
            `).join('');
        }

        // Render queue grid
        function renderQueueGrid() {
            // Update serving queues
            for (let i = 1; i <= 3; i++) {
                const servingQueue = servingQueues[i - 1];
                const servingElement = document.getElementById(`serving${i}`);
                const serviceElement = document.getElementById(`servingService${i}`);
                
                if (servingQueue) {
                    servingElement.textContent = servingQueue.number;
                    serviceElement.textContent = servingQueue.service;
                } else {
                    servingElement.textContent = '-';
                    serviceElement.textContent = '-';
                }
            }
        }

        // Show announcement
        function showAnnouncement(data) {
            document.getElementById('announcedNumber').textContent = data.queueNumber;
            document.getElementById('announcedService').textContent = data.serviceName;
            document.getElementById('announcedCounter').textContent = data.counterName;
            document.getElementById('announcedZone').textContent = data.zona;
            
            document.getElementById('announcementArea').classList.remove('hidden');
            document.getElementById('queueGrid').classList.add('hidden');
            
            // Play announcement sound
            const audio = document.getElementById('announcementAudio');
            audio.play().catch(e => console.log('Audio play failed:', e));
            
            // Auto-hide after 10 seconds
            setTimeout(() => {
                hideAnnouncement();
            }, 10000);
        }

        // Hide announcement
        function hideAnnouncement() {
            document.getElementById('announcementArea').classList.add('hidden');
            document.getElementById('queueGrid').classList.remove('hidden');
        }

        // Check for new announcements
        async function checkForAnnouncements() {
            try {
                const url = lastAnnouncementId
                    ? `/api/tv-display/latest-announcement?after_id=${encodeURIComponent(lastAnnouncementId)}`
                    : '/api/tv-display/latest-announcement';
                const response = await fetch(url);
                const data = await response.json();
                
                if (data && data.announcementId) {
                    lastAnnouncementId = data.announcementId;
                    showAnnouncement(data);
                }
            } catch (error) {
                console.error('Error checking for announcements:', error);
            }
        }

        // Initialize
        updateDateTime();
        fetchQueueStatus();
        
        // Update time every second
        setInterval(updateDateTime, 1000);
        
        // Update queue status every 3 seconds
        setInterval(fetchQueueStatus, 3000);
        
        // Check for announcements every 2 seconds
        setInterval(checkForAnnouncements, 2000);
        
        // Test function for manual testing
        window.testAnnouncement = function() {
            showAnnouncement({
                queueNumber: 'A001',
                serviceName: 'Pengambilan Izin',
                counterName: 'Loket 1A',
                zona: 'Zona 1'
            });
        };
    </script>
</body>
</html>
