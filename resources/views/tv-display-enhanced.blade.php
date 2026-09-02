<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mppBranding['name'] }} - TV Display</title>
    @vite(['resources/css/app.css'])
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            overflow: hidden;
            height: 100vh;
        }
        
        .service-card {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .service-card.active {
            border-left-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%);
        }
        
        .service-card.waiting {
            border-left-color: #f59e0b;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
        }
        
        .queue-number {
            font-size: clamp(3rem, 8vw, 12rem);
            font-weight: 900;
            line-height: 0.8;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
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
                transform: scale(1.02);
                box-shadow: 0 0 0 20px rgba(16, 185, 129, 0);
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
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 8px;
        }
        
        .status-serving {
            background-color: #10b981;
            box-shadow: 0 0 10px rgba(16, 185, 129, 0.5);
        }
        
        .status-available {
            background-color: #f59e0b;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.5);
        }
        
        .status-busy {
            background-color: #ef4444;
            box-shadow: 0 0 10px rgba(239, 68, 68, 0.5);
        }
        
        .announcement-card {
            background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        
        .service-icon {
            width: 24px;
            height: 24px;
            opacity: 0.7;
        }
        
        .time-display {
            font-variant-numeric: tabular-nums;
        }
    </style>
</head>
<body class="h-screen flex flex-col">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-900 via-blue-800 to-blue-700 text-white py-4 px-6 shadow-lg">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-black tracking-wide">{{ $mppBranding['name'] }}</h1>
                <p class="text-xl font-semibold mt-1 text-blue-100">KOTA SURABAYA</p>
                <p class="text-sm mt-1 text-blue-200">Jl. Tunjungan No.1-3, Genteng, Kec. Genteng, Surabaya, Jawa Timur 60275</p>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold time-display" id="currentTime"></div>
                <div class="text-lg text-blue-200" id="currentDate"></div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        <!-- Left Sidebar - Service Status -->
        <div class="w-1/4 bg-white border-r-2 border-gray-200 p-4 overflow-y-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center bg-gray-50 py-3 rounded-lg">
                STATUS LAYANAN
            </h2>
            
            <div class="space-y-3" id="serviceList">
                <!-- Service items will be populated by JavaScript -->
            </div>
        </div>

        <!-- Main Display Area -->
        <div class="flex-1 flex flex-col">
            <!-- Current Announcement -->
            <div id="announcementArea" class="hidden">
                <div class="announcement-card text-white p-8 m-4 rounded-2xl text-center">
                    <div class="flex items-center justify-center mb-6">
                        <div class="w-16 h-16 bg-white bg-opacity-20 rounded-full flex items-center justify-center mr-4">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 14.142M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-bold">PEMANGGILAN ANTRIAN</h3>
                    </div>
                    
                    <div class="bg-white bg-opacity-20 rounded-xl p-6 mb-6">
                        <div class="text-8xl font-black mb-4" id="announcedNumber">-</div>
                        <div class="text-2xl font-semibold" id="announcedService">-</div>
                    </div>
                    
                    <div class="text-xl mb-2">
                        <span class="font-semibold">Silakan menuju ke:</span>
                    </div>
                    <div class="text-3xl font-bold mb-2" id="announcedCounter">-</div>
                    <div class="text-lg opacity-90" id="announcedZone">-</div>
                </div>
            </div>

            <!-- Queue Status Grid -->
            <div id="queueGrid" class="flex-1 p-6">
                <div class="grid grid-cols-3 gap-6 h-full">
                    <!-- Serving Cards -->
                    <div class="bg-white rounded-2xl shadow-lg border-2 border-green-200 p-6 flex flex-col justify-center items-center text-center">
                        <div class="flex items-center mb-4">
                            <div class="status-indicator status-serving"></div>
                            <span class="text-lg font-semibold text-green-800">MELAYANI</span>
                        </div>
                        <div class="queue-number text-green-600" id="serving1">-</div>
                        <div class="text-lg font-medium text-gray-700 mt-2" id="servingService1">-</div>
                    </div>
                    
                    <div class="bg-white rounded-2xl shadow-lg border-2 border-green-200 p-6 flex flex-col justify-center items-center text-center">
                        <div class="flex items-center mb-4">
                            <div class="status-indicator status-serving"></div>
                            <span class="text-lg font-semibold text-green-800">MELAYANI</span>
                        </div>
                        <div class="queue-number text-green-600" id="serving2">-</div>
                        <div class="text-lg font-medium text-gray-700 mt-2" id="servingService2">-</div>
                    </div>
                    
                    <div class="bg-white rounded-2xl shadow-lg border-2 border-green-200 p-6 flex flex-col justify-center items-center text-center">
                        <div class="flex items-center mb-4">
                            <div class="status-indicator status-serving"></div>
                            <span class="text-lg font-semibold text-green-800">MELAYANI</span>
                        </div>
                        <div class="queue-number text-green-600" id="serving3">-</div>
                        <div class="text-lg font-medium text-gray-700 mt-2" id="servingService3">-</div>
                    </div>
                    
                    <!-- Available Cards -->
                    <div class="bg-white rounded-2xl shadow-lg border-2 border-yellow-200 p-6 flex flex-col justify-center items-center text-center">
                        <div class="flex items-center mb-4">
                            <div class="status-indicator status-available"></div>
                            <span class="text-lg font-semibold text-yellow-800">TERSEDIA</span>
                        </div>
                        <div class="text-6xl text-gray-400 mb-2">-</div>
                        <div class="text-lg text-gray-500">Belum ada panggilan</div>
                        <div class="text-sm text-gray-400 mt-2">Menunggu antrian berikutnya</div>
                    </div>
                    
                    <div class="bg-white rounded-2xl shadow-lg border-2 border-yellow-200 p-6 flex flex-col justify-center items-center text-center">
                        <div class="flex items-center mb-4">
                            <div class="status-indicator status-available"></div>
                            <span class="text-lg font-semibold text-yellow-800">TERSEDIA</span>
                        </div>
                        <div class="text-6xl text-gray-400 mb-2">-</div>
                        <div class="text-lg text-gray-500">Belum ada panggilan</div>
                        <div class="text-sm text-gray-400 mt-2">Menunggu antrian berikutnya</div>
                    </div>
                    
                    <div class="bg-white rounded-2xl shadow-lg border-2 border-yellow-200 p-6 flex flex-col justify-center items-center text-center">
                        <div class="flex items-center mb-4">
                            <div class="status-indicator status-available"></div>
                            <span class="text-lg font-semibold text-yellow-800">TERSEDIA</span>
                        </div>
                        <div class="text-6xl text-gray-400 mb-2">-</div>
                        <div class="text-lg text-gray-500">Belum ada panggilan</div>
                        <div class="text-sm text-gray-400 mt-2">Menunggu antrian berikutnya</div>
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
            }
        }

        // Render service list
        function renderServiceList() {
            const serviceList = document.getElementById('serviceList');
            serviceList.innerHTML = services.map(service => `
                <div class="service-card ${service.status} bg-white rounded-lg p-4 shadow-sm">
                    <div class="flex items-center mb-2">
                        <svg class="service-icon text-gray-600 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-800 text-sm">${service.name}</h3>
                            <p class="text-xs text-gray-600">Antrian Berikutnya</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-blue-600">${service.nextQueue || '-'}</p>
                        <p class="text-xs text-gray-500">• ${service.activeCounters}/${service.totalCounters} Loket Aktif</p>
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
            
            // Auto-hide after 8 seconds
            setTimeout(() => {
                hideAnnouncement();
            }, 8000);
        }

        // Hide announcement
        function hideAnnouncement() {
            document.getElementById('announcementArea').classList.add('hidden');
            document.getElementById('queueGrid').classList.remove('hidden');
        }

        // Check for new announcements
        let lastAnnouncementId = null;

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
