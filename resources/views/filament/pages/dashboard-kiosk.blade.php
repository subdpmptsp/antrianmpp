<div class="flex flex-col flex-grow p-4 lg:p-8 relative overflow-hidden" wire:poll.750ms="callNextQueue">
<!-- Fullscreen Button -->
    <button id="fullscreen-btn" class="fixed top-6 right-6 z-50 w-14 h-14 bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 flex items-center justify-center hover:bg-gradient-to-br hover:from-blue-600 hover:to-indigo-600 hover:text-white transition-all duration-300 group">
        <svg class="w-7 h-7 text-gray-700 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-5h-4m4 0v4m0-4l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path>
        </svg>
    </button>
    <!-- Header Section -->
    <div class="relative mb-8">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center space-x-4">
                <div
                    class="w-16 h-16 bg-gradient-to-br flex items-center justify-center">
                   <img src="{{asset('storage/'.$setting->image)}}" alt="">
                </div>
                <div>
                    <h1 class="text-xl md:text-3xl font-bold text-gray-800">{{ $setting->name }}</h1>
                    <p class="text-gray-600">{{ $setting->address }}</p>
                </div>
            </div>
        </div>
    </div>
    <div class="relative grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-6 lg:col-span-1">
            @php
                $serviceGroups = $counters->groupBy('service.name');
            @endphp

            @foreach ($serviceGroups as $serviceName => $serviceCounters)
                @php
                    $nextQueue = null;
                    $serviceCountersActive = $serviceCounters->where('is_active', true);

                    foreach ($serviceCountersActive as $counter) {
                        if ($counter->nextQueue) {
                            $nextQueue = $counter->nextQueue;
                            break;
                        }
                    }

                    $hasActiveCounters = $serviceCountersActive->count() > 0;
                    $hasAvailableCounters = $serviceCountersActive->where('is_available', true)->count() > 0;

                    $widgetColor = match (true) {
                        !$hasActiveCounters => 'gray',
                        $hasAvailableCounters && $nextQueue => 'blue',
                        $nextQueue => 'yellow',
                        default => 'green',
                    };

                    $colorClasses = [
                        'blue' => [
                            'bg' => 'bg-blue-100',
                            'text' => 'text-blue-600',
                            'icon' => 'text-blue-600',
                            'number' => 'text-blue-700',
                        ],
                        'yellow' => [
                            'bg' => 'bg-yellow-100',
                            'text' => 'text-yellow-600',
                            'icon' => 'text-yellow-600',
                            'number' => 'text-yellow-700',
                        ],
                        'green' => [
                            'bg' => 'bg-green-100',
                            'text' => 'text-green-600',
                            'icon' => 'text-green-600',
                            'number' => 'text-green-700',
                        ],
                        'gray' => [
                            'bg' => 'bg-gray-100',
                            'text' => 'text-gray-600',
                            'icon' => 'text-gray-600',
                            'number' => 'text-gray-700',
                        ],
                    ];

                    $colors = $colorClasses[$widgetColor];
                @endphp

                {{-- Box untuk setiap layanan (next queue) --}}
                <div class="bg-white rounded-lg shadow border p-4 hover:shadow-lg transition">
                    <div class="flex items-center justify-center space-x-3 mb-3">
                        <div class="w-8 h-8 {{ $colors['bg'] }} rounded-lg flex items-center justify-center">
                            <svg class="w-4 h-4 {{ $colors['icon'] }}" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700">{{ $serviceName }}</p>
                            <p class="text-sm text-gray-400">Antrian Berikutnya</p>
                        </div>
                    </div>

                    <div class="text-center">
                        @if ($nextQueue)
                            <div class="text-5xl font-bold {{ $colors['number'] }}">{{ $nextQueue->number }}</div>
                            <p class="text-sm mt-1 {{ $colors['text'] }}">Segera Dipanggil</p>
                        @else
                            <div class="text-lg text-gray-400">Tidak ada antrian</div>
                        @endif
                    </div>

                    <div class="flex items-center justify-center mt-4 border-t pt-2">
                        <div
                            class="w-2 h-2 {{ $hasActiveCounters ? 'bg-green-500' : 'bg-gray-400' }} rounded-full mr-2">
                        </div>
                        <span
                            class="text-sm text-gray-500">{{ $serviceCountersActive->count() }}/{{ $serviceCounters->count() }}
                            Loket Aktif</span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- 📌 KANAN: COUNTER LISTING --}}
        <div class="grid grid-cols-1 md:grid-cols-2 2xl:grid-cols-3 gap-6 lg:col-span-2">
            @foreach ($counters as $counter)
                {{-- Menentukan warna tema berdasarkan status loket --}}
                @php
                    $themeColors = [
                        'blue' => [
                            'bg' => 'bg-blue-500',
                            'text' => 'text-blue-600',
                            'badge' => 'bg-blue-100 text-blue-800',
                            'border' => 'border-blue-500',
                            'gradient' => 'from-blue-500 to-blue-600',
                        ],
                        'green' => [
                            'bg' => 'bg-green-500',
                            'text' => 'text-green-600',
                            'badge' => 'bg-green-100 text-green-800',
                            'border' => 'border-green-500',
                            'gradient' => 'from-green-500 to-green-600',
                        ],
                        'yellow' => [
                            'bg' => 'bg-yellow-500',
                            'text' => 'text-yellow-600',
                            'badge' => 'bg-yellow-100 text-yellow-800',
                            'border' => 'border-yellow-500',
                            'gradient' => 'from-yellow-500 to-yellow-600',
                        ],
                        'red' => [
                            'bg' => 'bg-red-500',
                            'text' => 'text-red-600',
                            'badge' => 'bg-red-100 text-red-800',
                            'border' => 'border-red-500',
                            'gradient' => 'from-red-500 to-red-600',
                        ],
                        'gray' => [
                            'bg' => 'bg-gray-500',
                            'text' => 'text-gray-600',
                            'badge' => 'bg-gray-100 text-gray-800',
                            'border' => 'border-gray-500',
                            'gradient' => 'from-gray-500 to-gray-600',
                        ],
                    ];

                    $themeColor = 'gray'; // Default color for inactive
                    if ($counter->is_active && $counter->activeQueue) {
                        $themeColor = $counter->is_available ? 'blue' : 'green';
                    } else {
                        $themeColor = $counter->is_available ? 'yellow' : 'red';
                    }

                    $colors = $themeColors[$themeColor];
                @endphp

                <div
                    class="group relative bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg border border-gray-200/50 transition-all duration-500 ease-in-out transform hover:-translate-y-2 hover:shadow-2xl overflow-hidden">

                    <!-- Gradient Border Effect -->
                    <div
                        class="absolute inset-0 bg-gradient-to-r {{ $colors['gradient'] }} opacity-0 group-hover:opacity-100 transition-opacity duration-300 rounded-2xl blur-sm">
                    </div>
                    <div class="absolute inset-[1px] bg-white rounded-2xl"></div>

                    <!-- Card Content -->
                    <div class="relative flex flex-col h-full">

                        <!-- Header -->
                        <div class="flex justify-between items-center p-6 border-b border-gray-100">
                            <div class="flex items-center space-x-3">
                                <div
                                    class="w-10 h-10 bg-gradient-to-br {{ $colors['gradient'] }} rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                        </path>
                                    </svg>
                                </div>
                                <h2 class="text-3xl font-bold text-gray-800">{{ $counter->name }}</h2>
                            </div>

                            <!-- Status Badge -->
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 {{ $colors['bg'] }} rounded-full animate-pulse"></div>
                                <span class="px-3 py-1 text-sm font-semibold {{ $colors['badge'] }} rounded-full">
                                    @if (!$counter->is_active)
                                        Tutup
                                    @elseif($counter->is_available)
                                        Tersedia
                                    @else
                                        Melayani
                                    @endif
                                </span>
                            </div>
                        </div>

                        <!-- Main Content -->
                        <div class="flex-grow flex flex-col items-center justify-center p-8 text-center">
                            @if ($counter->is_active)
                                @if ($counter->activeQueue)
                                    <div class="mb-4">
                                        <p class="text-sm text-gray-500 mb-2 font-medium">Nomor Antrian</p>
                                        <div class="relative">
                                            <div
                                                class="absolute inset-0 bg-gradient-to-r {{ $colors['gradient'] }} opacity-10 rounded-2xl blur-xl">
                                            </div>
                                            <div
                                                class="relative text-8xl font-black {{ $colors['text'] }} tracking-wider drop-shadow-lg">
                                                {{ $counter->activeQueue->number }}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="px-4 py-2 bg-gradient-to-r {{ $colors['gradient'] }} text-white rounded-full shadow-lg">
                                        <p class="text-lg font-semibold">{{ $counter->activeQueue->kiosk_label }}</p>
                                    </div>
                                @else
                                    <div class="flex flex-col items-center justify-center text-gray-500">
                                        <div
                                            class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M4 8h16M4 16h16"></path>
                                            </svg>
                                        </div>
                                        <p class="text-lg font-semibold">Belum ada panggilan</p>
                                        <p class="text-sm text-gray-400 mt-1">Menunggu antrian berikutnya</p>
                                    </div>
                                @endif
                            @else
                                <div class="flex flex-col items-center justify-center text-gray-500">
                                    <div
                                        class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M12 15v2m0 0v2m0-2h2m-2 0H10m8-7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-2xl font-bold text-gray-600">Sedang Tutup</p>
                                    <p class="text-sm text-gray-400 mt-1">Loket tidak aktif</p>
                                </div>
                            @endif
                        </div>

                        <!-- Footer -->
                        <div
                            class="bg-gradient-to-r from-gray-50 to-gray-100 p-4 rounded-b-2xl border-t border-gray-100">
                            <div class="flex items-center justify-center space-x-2">
                                <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-sm">
                                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6">
                                        </path>
                                    </svg>
                                </div>
                                <p class="text-lg font-semibold text-gray-700">{{ $counter->service->name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

    </div>


</div>

@push('styles')
    <style>
        .counter-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .counter-card:hover {
            transform: translateY(-8px) scale(1.02);
        }

        .queue-number {
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            animation: pulse-glow 2s ease-in-out infinite alternate;
        }

        @keyframes pulse-glow {
            from {
                filter: drop-shadow(0 0 20px rgba(59, 130, 246, 0.5));
            }

            to {
                filter: drop-shadow(0 0 30px rgba(59, 130, 246, 0.8));
            }
        }

        .status-indicator {
            animation: status-pulse 2s ease-in-out infinite;
        }

        @keyframes status-pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .floating-animation {
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }
    </style>
@endpush
