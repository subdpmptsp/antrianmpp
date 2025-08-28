<x-filament-panels::page>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body, .fi-body {
            font-family: 'Poppins', sans-serif !important;
        }
        
        .animate-pulse-slow {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .counter-button {
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .counter-button:hover {
            transform: translateY(-2px);
        }
        
        .counter-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .counter-button:hover::before {
            left: 100%;
        }
        
        .number-display {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            box-shadow: 0 25px 50px -12px rgba(59, 130, 246, 0.25);
        }
        
        .status-badge {
            animation: pulse 2s infinite;
        }
    </style>

    <div wire:poll.5s class="space-y-6">
        <!-- Header Section -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 14.142M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Loket Panggilan Antrian</h1>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Sistem manajemen antrian terintegrasi</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="status-badge w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Live Update</span>
                </div>
            </div>
            
            <!-- Counter Selection -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @forelse ($counters as $counter)
                    <button type="button" wire:click="selectCounter({{ $counter->id }})"
                        class="counter-button relative p-4 rounded-xl font-semibold transition-all duration-300 shadow-sm hover:shadow-lg {{ $selectedCounterId == $counter->id
                            ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg'
                            : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-2 border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500' }}">
                        <div class="text-center">
                            <div class="text-lg font-bold">{{ $counter->name }}</div>
                            <div class="text-sm opacity-90">{{ $counter->service->name }}</div>
                            <div class="flex items-center justify-center mt-2">
                                <div class="w-2 h-2 {{ $counter->is_active ? 'bg-green-400' : 'bg-gray-400' }} rounded-full mr-2"></div>
                                <span class="text-xs">{{ $counter->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </div>
                        </div>
                    </button>
                @empty
                    <div class="col-span-full text-center py-8">
                        <p class="text-gray-500 dark:text-gray-400 text-lg">Tidak ada loket yang aktif saat ini.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Main Content --}}
        @if ($this->selectedCounter)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Current Queue Section -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Current Patient Card -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden card-hover">
                        @if ($currentQueue)
                            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 border-b border-gray-100 dark:border-gray-600">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900">Nomor Antrian Saat Ini</h3>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-3 h-3 {{ $currentQueue->status == 'waiting' ? 'bg-yellow-500' : 'bg-green-500' }} rounded-full animate-pulse"></div>
                                        <span class="text-sm font-medium text-gray-600 ">
                                            {{ $currentQueue->status == 'waiting' ? 'Menunggu Dipanggil' : 'Sedang Dilayani' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-8" wire:key="current-{{ $currentQueue->id }}">
                                <div class="text-center mb-8">
                                    <div class="relative inline-block">
                                        <div class="number-display w-48 h-48 rounded-3xl flex items-center justify-center shadow-2xl">
                                            <span class="text-6xl font-bold text-white">{{ $currentQueue->number }}</span>
                                        </div>
                                        <div class="absolute -top-4 -right-4 w-12 h-12 bg-{{ $currentQueue->status == 'waiting' ? 'yellow' : 'green' }}-500 rounded-full flex items-center justify-center shadow-lg">
                                            @if($currentQueue->status == 'waiting')
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @else
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Layanan</p>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $currentQueue->service->name }}</p>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="grid grid-cols-1 md:grid-cols-{{ $currentQueue->status == 'waiting' ? '3' : '1' }} gap-4">
                                    @if ($currentQueue->status == 'waiting')
                                        <button wire:click="callNext"
                                            class="group relative overflow-hidden bg-blue-500 text-white py-4 px-6 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                            <div class="relative z-10 flex items-center justify-center space-x-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 14.142M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                                                </svg>
                                                <span>Panggil Lagi</span>
                                            </div>
                                        </button>
                                        
                                        <button wire:click="markAsServing({{ $currentQueue->id }})"
                                            class="group relative overflow-hidden bg-green-600 text-white py-4 px-6 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                            <div class="relative z-10 flex items-center justify-center space-x-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span>Layani</span>
                                            </div>
                                        </button>
                                        
                                        <button wire:click="markAsCancelled({{ $currentQueue->id }})"
                                            class="group relative overflow-hidden bg-red-500 text-white py-4 px-6 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                            <div class="relative z-10 flex items-center justify-center space-x-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                <span>Batalkan</span>
                                            </div>
                                        </button>
                                    @else
                                        <button wire:click="markAsFinished({{ $currentQueue->id }})"
                                            class="group relative overflow-hidden bg-green-600 text-white py-4 px-6 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                            <div class="relative z-10 flex items-center justify-center space-x-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span>Selesai Dilayani</span>
                                            </div>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <div class="p-8">
                                <div class="text-center bg-gray-50 dark:bg-gray-700 rounded-2xl p-12">
                                    <div class="w-20 h-20 bg-gray-200 dark:bg-gray-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-10 h-10 text-gray-400 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <p class="text-xl font-semibold text-gray-500 ">Tidak ada pasien yang sedang dipanggil</p>
                                    <p class="text-sm text-gray-400 mt-2">Klik tombol "Panggil Antrian" untuk memulai</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Waiting Queue List -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 card-hover">
                        <div class="p-6 border-b border-gray-100 dark:border-gray-600">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Daftar Antrian</h3>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                    {{ $waitingQueues->count() }} Menunggu
                                </span>
                            </div>
                            
                            <button wire:click="callNext" 
                                @if (!$this->selectedCounter->is_active) disabled @endif
                                class="w-full py-4 px-6 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 text-lg {{ !$this->selectedCounter->is_active 
                                    ? 'bg-gray-300 dark:bg-gray-600 text-gray-500 dark:text-gray-400 cursor-not-allowed' 
                                    : 'bg-gradient-to-r from-blue-500 to-blue-600 text-white hover:from-blue-600 hover:to-blue-700 hover:scale-105' }}">
                                <div class="flex items-center justify-center space-x-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 14.142M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                                    </svg>
                                    <span>Panggil Antrian Selanjutnya</span>
                                </div>
                            </button>
                        </div>
                        
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($waitingQueues as $queue)
                                <div class="p-6 transition-colors duration-200" wire:key="waiting-{{ $queue->id }}">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center shadow-lg">
                                                <span class="text-xl font-bold text-white">{{ $queue->number }}</span>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $queue->service->name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $queue->created_at->format('H:i, d M Y') }}</p>
                                                <div class="flex items-center mt-1">
                                                    <div class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></div>
                                                    <span class="text-xs text-gray-600 dark:text-gray-200">Menunggu</span>
                                                </div>
                                            </div>
                                        </div>
                                        <button wire:click="markAsCancelled({{ $queue->id }})"
                                            class="text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 p-3 rounded-xl hover:bg-red-50 dark:hover:bg-red-900 transition-colors duration-200">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="p-8 text-center">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <svg class="w-8 h-8 text-gray-400 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 4h.01M9 16h.01"></path>
                                        </svg>
                                    </div>
                                    <p class="text-gray-500 dark:text-gray-400 font-medium">Daftar antrian kosong</p>
                                    <p class="text-sm text-gray-400 mt-1">Belum ada antrian yang menunggu</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Counter Status -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 card-hover">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Status Loket</h3>
                        <div class="text-center">
                            <div class="w-20 h-20 bg-gradient-to-br from-{{ $this->selectedCounter->is_active ? 'green' : 'red' }}-500 to-{{ $this->selectedCounter->is_active ? 'green' : 'red' }}-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                                @if($this->selectedCounter->is_active)
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                @else
                                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">{{ $this->selectedCounter->name }} - {{ $this->selectedCounter->service->name }}</p>
                            <p class="text-xl font-bold text-{{ $this->selectedCounter->is_active ? 'green' : 'red' }}-600 mb-6">
                                {{ $this->selectedCounter->is_active ? 'SEDANG BUKA' : 'SEDANG TUTUP' }}
                            </p>
                            <button wire:click="toggleCounterStatus"
                                class="w-full bg-{{ $this->selectedCounter->is_active ? 'red' : 'green' }}-500 text-white py-3 px-4 rounded-xl font-semibold hover:bg-{{ $this->selectedCounter->is_active ? 'red' : 'green' }}-600 transition-colors duration-200 shadow-lg hover:shadow-xl">
                                {{ $this->selectedCounter->is_active ? 'Tutup Loket' : 'Buka Loket' }}
                            </button>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 card-hover">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-6">Statistik Hari Ini</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-blue-500 rounded-xl flex items-center justify-center shadow-md">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 ">Total Pasien</span>
                                </div>
                                <span class="text-2xl font-bold text-gray-900 ">{{ $stats['total'] }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between p-4 bg-green-50 rounded-xl">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-green-500 rounded-xl flex items-center justify-center shadow-md">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 ">Selesai</span>
                                </div>
                                <span class="text-2xl font-bold text-green-600">{{ $stats['finished'] }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between p-4 bg-yellow-50 rounded-xl">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-yellow-500 rounded-xl flex items-center justify-center shadow-md">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 ">Menunggu</span>
                                </div>
                                <span class="text-2xl font-bold text-yellow-600">{{ $stats['waiting'] }}</span>
                            </div>
                            
                            <div class="flex items-center justify-between p-4 bg-red-50 rounded-xl">
                                <div class="flex items-center space-x-3">
                                    <div class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center shadow-md">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 ">Batal/Lewat</span>
                                </div>
                                <span class="text-2xl font-bold text-red-600">{{ $stats['cancelled'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-12 h-12 text-gray-400 " fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-2">Silakan pilih loket</h3>
                <p class="text-gray-500 dark:text-gray-400">Pilih loket terlebih dahulu untuk memulai manajemen antrian</p>
            </div>
        @endif
    </div>
</x-filament-panels::page>