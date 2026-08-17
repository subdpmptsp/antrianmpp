<x-filament-panels::page>
    
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
            
            <!-- Counter Selection (hidden for operators) -->
            @php $user = auth()->user(); @endphp
            @if(!$user || $user->role !== 'operator')
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    @forelse ($counters as $counter)
                        <button type="button" wire:click="selectCounter({{ $counter->id }})"
                            class="counter-button relative p-4 rounded-xl font-semibold transition-all duration-300 shadow-sm hover:shadow-lg {{ $selectedCounterId == $counter->id
                                ? 'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-lg'
                                : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border-2 border-gray-200 dark:border-gray-600 hover:border-gray-300 dark:hover:border-gray-500' }}">
                            <div class="text-center">
                                <div class="text-lg font-bold">{{ $counter->name }}</div>
                                <div class="text-sm opacity-90 mt-2">
                                    @if($counter->service)
                                        <span class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs px-2 py-1 rounded-full">
                                            {{ $counter->service->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-500">Tidak ada layanan</span>
                                    @endif
                                </div>
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
            @endif
        </div>

        <!-- Audio Enable Notice -->
        <div id="audioNotice" class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-yellow-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 14.142M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                </svg>
                <span class="text-yellow-800 font-medium">Klik di mana saja untuk mengaktifkan suara pemanggilan</span>
            </div>
        </div>

        {{-- Main Content --}}
        @php
            $selectedCounter = $this->selectedCounter;
            $selectedCounterId = $this->selectedCounterId;
        @endphp
        
        @if ($selectedCounter)
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
                                        <div class="w-3 h-3 {{ $currentQueue->status == 'called' ? 'bg-yellow-500' : ($currentQueue->status == 'serving' ? 'bg-green-500' : 'bg-gray-500') }} rounded-full animate-pulse"></div>
                                        <span class="text-sm font-medium text-gray-600 ">
                                            {{ $currentQueue->status == 'called' ? 'Menunggu Dipanggil' : ($currentQueue->status == 'serving' ? 'Sedang Dilayani' : 'Status Tidak Diketahui') }}
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
                                        <div class="absolute -top-4 -right-4 w-12 h-12 bg-{{ $currentQueue->status == 'called' ? 'yellow' : ($currentQueue->status == 'serving' ? 'green' : 'gray') }}-500 rounded-full flex items-center justify-center shadow-lg">
                                            @if($currentQueue->status == 'called')
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @elseif($currentQueue->status == 'serving')
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            @else
                                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Layanan</p>
                                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $currentQueue->service?->name ?? 'Tidak ada layanan' }}</p>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="grid grid-cols-1 md:grid-cols-{{ $currentQueue->status == 'called' ? '3' : '1' }} gap-4">
                                    @if ($currentQueue->status == 'called')
                                        <button wire:click="callAgain({{ $currentQueue->id }})"
                                            class="group relative overflow-hidden bg-blue-500 text-white py-4 px-6 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                            <div class="relative z-10 flex items-center justify-center space-x-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 14.142M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                                                </svg>
                                                <span>Panggil Lagi</span>
                                            </div>
                                        </button>
                                        
                                        <button wire:click="startServing({{ $currentQueue->id }})"
                                            class="group relative overflow-hidden bg-green-600 text-white py-4 px-6 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                            <div class="relative z-10 flex items-center justify-center space-x-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span>Layani</span>
                                            </div>
                                        </button>
                                        
                                        <button wire:click="cancelCalled({{ $currentQueue->id }})"
                                            class="group relative overflow-hidden bg-red-500 text-white py-4 px-6 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                                            <div class="relative z-10 flex items-center justify-center space-x-2">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                <span>Batalkan</span>
                                            </div>
                                        </button>
                                    @elseif ($currentQueue->status == 'serving')
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
                            
                            <div class="space-y-3">
                            <button wire:click="callNext" 
                                @if (!$selectedCounter || !$selectedCounter->is_active) disabled @endif
                                class="w-full py-4 px-6 rounded-xl font-semibold shadow-lg hover:shadow-xl transition-all duration-300 text-lg {{ !$selectedCounter || !$selectedCounter->is_active 
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
                                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $queue->service?->name ?? 'Tidak ada layanan' }}</p>
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
                        @if($selectedCounter)
                            <div class="text-center">
                                <div class="w-20 h-20 bg-gradient-to-br from-{{ $selectedCounter->is_active ? 'green' : 'red' }}-500 to-{{ $selectedCounter->is_active ? 'green' : 'red' }}-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg">
                                    @if($selectedCounter->is_active)
                                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    @else
                                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-2 font-semibold">
                                    {{ strtoupper($selectedCounter->name) }}
                                </p>
                                <div class="mb-4">
                                    @if($selectedCounter->service)
                                        <span class="inline-block bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-xs px-3 py-1.5 rounded-full font-medium">
                                            {{ $selectedCounter->service->name }}
                                        </span>
                                    @else
                                        <span class="text-gray-500 text-sm">Tidak ada layanan</span>
                                    @endif
                                </div>
                                <p class="text-xl font-bold text-{{ $selectedCounter->is_active ? 'green' : 'red' }}-600 mb-6">
                                    {{ $selectedCounter->is_active ? 'SEDANG BUKA' : 'SEDANG TUTUP' }}
                                </p>
                                <button wire:click="toggleCounterStatus"
                                    class="w-full bg-{{ $selectedCounter->is_active ? 'red' : 'green' }}-500 text-white py-3 px-4 rounded-xl font-semibold hover:bg-{{ $selectedCounter->is_active ? 'red' : 'green' }}-600 transition-colors duration-200 shadow-lg hover:shadow-xl">
                                    {{ $selectedCounter->is_active ? 'Tutup Loket' : 'Buka Loket' }}
                                </button>
                            </div>
                        @else
                            <div class="text-center text-gray-500 dark:text-gray-400">
                                <p>Pilih loket untuk melihat status</p>
                            </div>
                        @endif
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
                                    <span class="text-sm font-medium text-gray-700 ">Total Pemohon</span>
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
                @php
                    $user = auth()->user();
                @endphp
                @if($user && $user->role === 'operator' && $user->counter_id)
                    <h3 class="text-xl font-semibold text-yellow-600 dark:text-yellow-400 mb-2">Memuat loket...</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Sedang memuat loket dengan ID {{ $user->counter_id }}.
                    </p>
                    <p class="text-sm text-gray-400">
                        Jika loket tidak muncul, silakan refresh halaman atau hubungi administrator.
                    </p>
                    <div class="mt-4">
                        <button wire:click="$refresh" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                            Refresh Halaman
                        </button>
                    </div>
                @else
                    <h3 class="text-xl font-semibold text-gray-700 dark:text-gray-200 mb-2">Silakan pilih loket</h3>
                    <p class="text-gray-500 dark:text-gray-400">Pilih loket terlebih dahulu untuk memulai manajemen antrian</p>
                @endif
            </div>
        @endif

    </div>

    <!-- Audio untuk pemanggilan -->
    <audio id="announcementAudio" preload="auto">
        <source src="/sounds/announcement.mp3" type="audio/mpeg">
        <source src="/sounds/announcement.wav" type="audio/wav">
    </audio>

    <!-- ResponsiveVoice Script -->

    <!-- Notification untuk tampilan TV -->
    <div id="tvNotification" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full mx-4 text-center">
                <div class="w-20 h-20 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 14.142M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Pemanggilan Antrian</h2>
                <div class="text-4xl font-bold text-blue-600 mb-4" id="announcedQueueNumber">-</div>
                <div class="text-lg text-gray-600 mb-2" id="announcedService">-</div>
                <div class="text-sm text-gray-500" id="announcedCounter">-</div>
                <div class="text-xs text-gray-400 mt-4" id="announcedTime">-</div>
            </div>
        </div>
    </div>

    <script>
        // ResponsiveVoice configuration
        const RESPONSIVEVOICE_CONFIG = {
            voice: 'Indonesian Female',
            rate: 0.8,
            pitch: 1,
            volume: 1
        };
        
        // Enable audio on first user interaction
        let audioEnabled = false;
        const enableAudio = () => {
            if (!audioEnabled) {
                console.log('Enabling audio on user interaction...');
                audioEnabled = true;
                
                // Hide audio notice
                const audioNotice = document.getElementById('audioNotice');
                if (audioNotice) {
                    audioNotice.style.display = 'none';
                }
                
                // Don't play test audio here, just enable
                console.log('Audio enabled successfully');
            }
        };
        
        // Expose enableAudio to global scope
        window.enableAudio = () => {
            console.log('enableAudio called from button click');
            enableAudio();
        };
        
        
        document.addEventListener('livewire:initialized', () => {
            console.log('Livewire initialized, setting up event listeners...');
            const audio = document.getElementById('announcementAudio');
            const tvNotification = document.getElementById('tvNotification');
            
            console.log('Audio element:', audio);
            console.log('TV notification element:', tvNotification);
            
            // Enable audio context on first user interaction
            let audioEnabled = false;
            const enableAudio = () => {
                if (!audioEnabled) {
                    console.log('Enabling audio on user interaction...');
                    audioEnabled = true;
                    
                    // Hide audio notice
                    const audioNotice = document.getElementById('audioNotice');
                    if (audioNotice) {
                        audioNotice.style.display = 'none';
                    }
                    
                    // Audio enabled silently without test sound
                }
            };
            
            // Add click listeners to enable audio
            document.addEventListener('click', enableAudio, { once: true });
            document.addEventListener('keydown', enableAudio, { once: true });
            
            // Event listener untuk pemanggilan antrian
            Livewire.on('announce-queue', (data) => {
                console.log('=== ANNOUNCE QUEUE EVENT RECEIVED ===');
                console.log('Data received:', data);
                
                // Extract data dari object
                let announcementData;
                if (Array.isArray(data) && data.length > 0) {
                    announcementData = data[0];
                } else if (typeof data === 'object' && data !== null) {
                    announcementData = data;
                } else {
                    console.error('Invalid data format received:', data);
                    return;
                }
                
                console.log('Announcement data:', announcementData);
                
                // Update tampilan TV
                const queueNumberEl = document.getElementById('announcedQueueNumber');
                const serviceEl = document.getElementById('announcedService');
                const counterEl = document.getElementById('announcedCounter');
                const timeEl = document.getElementById('announcedTime');
                
                if (queueNumberEl) queueNumberEl.textContent = announcementData.queueNumber;
                if (serviceEl) serviceEl.textContent = announcementData.serviceName;
                if (counterEl) counterEl.textContent = `${announcementData.counterName} - ${announcementData.zona}`;
                if (timeEl) timeEl.textContent = `Dipanggil pada: ${announcementData.calledAt}`;
                
                // Tampilkan notifikasi TV
                if (tvNotification) {
                    tvNotification.classList.remove('hidden');
                }
                
                // SELALU putar suara pemanggilan - ini akan diputar di browser petugas loket
                // Tidak peduli apakah audio sudah di-enable sebelumnya atau belum
                playAnnouncementSound(announcementData);
                
                // Sembunyikan notifikasi setelah 10 detik (lebih lama untuk memastikan audio selesai)
                setTimeout(() => {
                    if (tvNotification) {
                        tvNotification.classList.add('hidden');
                    }
                }, 10000);
            });
            
            function playAnnouncementSound(data) {
                console.log('=== PLAYING ANNOUNCEMENT SOUND ===');
                console.log('Data:', data);
                
                // Langsung gunakan ResponsiveVoice untuk audio announcement
                playResponsiveVoiceAnnouncement(data);
            }
            
            function playResponsiveVoiceAnnouncement(data) {
                console.log('=== PLAYING RESPONSIVEVOICE ANNOUNCEMENT ===');
                console.log('Data:', data);
                
                // SELALU enable audio ketika ada panggilan, tidak peduli status sebelumnya
                if (!audioEnabled) {
                    console.log('Audio not enabled yet, enabling now...');
                    audioEnabled = true;
                    
                    // Hide audio notice
                    const audioNotice = document.getElementById('audioNotice');
                    if (audioNotice) {
                        audioNotice.style.display = 'none';
                    }
                }
                
                // Ganti tanda minus dengan spasi agar tidak terbaca oleh audio
                const queueNumber = (data.queueNumber || 'Tidak diketahui').replace(/-/g, ' ');
                const serviceName = (data.serviceName || 'Layanan').toLowerCase();
                const servicePrefix = data.servicePrefix || 'A';
                const zona = data.zona || 'Zona'; // Sekarang menggunakan counter.name
                
                // Format zona - UPTSP dieja, yang lain tidak
                let zonaText = zona.toLowerCase();
                if (zona.toUpperCase() === 'UPTSP') {
                    zonaText = 'U-P-T-S-P';
                }
                
                // Cek apakah serviceName sudah mengandung kata "layanan"
                let finalServiceName = serviceName;
                if (!serviceName.includes('layanan')) {
                    finalServiceName = `layanan ${serviceName}`;
                }
                
                // Format audio announcement sesuai dengan yang diminta
                const announcementText = `nomor antrian ${queueNumber} menuju ke loket ${servicePrefix}, ${finalServiceName} ${zonaText}`;
                
                console.log('Announcement text:', announcementText);
                
                // SELALU coba putar audio, tidak peduli status audioEnabled
                // Ini memastikan suara selalu diputar di browser petugas loket
                tryAudioSolutions(announcementText, data);
            }
            
            function tryAudioSolutions(announcementText, data) {
                console.log('=== TRYING AUDIO SOLUTIONS ===');
                console.log('Announcement text:', announcementText);
                console.log('ResponsiveVoice available:', typeof responsiveVoice !== 'undefined');
                console.log('Speech Synthesis available:', 'speechSynthesis' in window);
                
                // Solution 1: Try ResponsiveVoice (PRIORITAS UTAMA)
                if (typeof responsiveVoice !== 'undefined') {
                    console.log('Trying ResponsiveVoice...');
                    console.log('Voice:', RESPONSIVEVOICE_CONFIG.voice);
                    console.log('Rate:', RESPONSIVEVOICE_CONFIG.rate);
                    console.log('Pitch:', RESPONSIVEVOICE_CONFIG.pitch);
                    console.log('Volume:', RESPONSIVEVOICE_CONFIG.volume);
                    
                    try {
                        // Stop any ongoing speech first
                        responsiveVoice.cancel();
                        
                        // Wait a bit before speaking to ensure clean start
                        setTimeout(() => {
                            responsiveVoice.speak(announcementText, RESPONSIVEVOICE_CONFIG.voice, {
                                rate: RESPONSIVEVOICE_CONFIG.rate,
                                pitch: RESPONSIVEVOICE_CONFIG.pitch,
                                volume: RESPONSIVEVOICE_CONFIG.volume,
                                onstart: function() {
                                    console.log('✅ ResponsiveVoice started successfully');
                                },
                                onend: function() {
                                    console.log('✅ ResponsiveVoice completed successfully');
                                },
                                onerror: function(error) {
                                    console.error('❌ ResponsiveVoice failed:', error);
                                    // Try next solution
                                    trySpeechSynthesis(announcementText);
                                }
                            });
                        }, 100);
                        return; // Success, exit
                    } catch (error) {
                        console.error('❌ ResponsiveVoice error:', error);
                        // Continue to next solution
                    }
                } else {
                    console.log('❌ ResponsiveVoice not available');
                }
                
                // Solution 2: Try Speech Synthesis (FALLBACK)
                trySpeechSynthesis(announcementText);
            }
            
            function trySpeechSynthesis(announcementText) {
                console.log('Trying Speech Synthesis...');
                console.log('Announcement text:', announcementText);
                
                if ('speechSynthesis' in window) {
                    try {
                        // Cancel any ongoing speech first
                        speechSynthesis.cancel();
                        
                        // Wait a bit before speaking to ensure clean start
                        setTimeout(() => {
                            const utterance = new SpeechSynthesisUtterance(announcementText);
                            utterance.lang = 'id-ID';
                            utterance.rate = 0.8;
                            utterance.pitch = 1;
                            utterance.volume = 1;
                            
                            console.log('Speech Synthesis settings:');
                            console.log('  Language:', utterance.lang);
                            console.log('  Rate:', utterance.rate);
                            console.log('  Pitch:', utterance.pitch);
                            console.log('  Volume:', utterance.volume);
                            
                            utterance.onstart = function() {
                                console.log('✅ Speech Synthesis started successfully');
                            };
                            
                            utterance.onend = function() {
                                console.log('✅ Speech Synthesis completed successfully');
                            };
                            
                            utterance.onerror = function(error) {
                                console.error('❌ Speech Synthesis failed:', error);
                                // Try next solution
                                tryAudioFile();
                            };
                            
                            speechSynthesis.speak(utterance);
                        }, 100);
                        return; // Success, exit
                    } catch (error) {
                        console.error('❌ Speech Synthesis error:', error);
                    }
                } else {
                    console.log('❌ Speech Synthesis not available');
                }
                
                // Solution 3: Try Audio File
                tryAudioFile();
            }
            
            function tryAudioFile() {
                console.log('Trying Audio File...');
                
                try {
                    const audio = new Audio('/sounds/opening.mp3');
                    audio.volume = 0.8;
                    
                    audio.oncanplaythrough = function() {
                        console.log('✅ Audio file can play');
                        audio.play().then(function() {
                            console.log('✅ Audio file playing successfully');
                        }).catch(function(error) {
                            console.error('❌ Audio file play failed:', error);
                            // Try next solution
                            tryBeepSound();
                        });
                    };
                    
                    audio.onerror = function(error) {
                        console.error('❌ Audio file error:', error);
                        // Try next solution
                        tryBeepSound();
                    };
                    
                    audio.load();
                } catch (error) {
                    console.error('❌ Audio file error:', error);
                    // Try next solution
                    tryBeepSound();
                }
            }
            
            function tryBeepSound() {
                console.log('Trying Beep Sound...');
                
                try {
                    // Create a simple beep sound using Web Audio API
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                    oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);
                    oscillator.frequency.setValueAtTime(800, audioContext.currentTime + 0.2);
                    
                    gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.5);
                    
                    oscillator.start(audioContext.currentTime);
                    oscillator.stop(audioContext.currentTime + 0.5);
                    
                    console.log('✅ Beep sound played successfully');
                } catch (error) {
                    console.error('❌ Beep sound failed:', error);
                    console.log('❌ All audio solutions failed');
                }
            }
            
            function fetchAudioFromAPI(data) {
                console.log('=== FETCHING AUDIO FROM API ===');
                
                const params = new URLSearchParams({
                    queue_id: data.queueId || ''
                });
                
                fetch(`/api/audio/announcement?${params}`)
                    .then(response => response.json())
                    .then(result => {
                        console.log('API Response:', result);
                        
                        if (result.success && result.audioUrl) {
                            console.log('Using audio from API:', result.audioUrl);
                            playAudioFromUrl(result.audioUrl);
                        } else {
                            console.log('API failed, falling back to speech synthesis');
                            speakAnnouncement(data);
                        }
                    })
                    .catch(error => {
                        console.error('API Error:', error);
                        console.log('API failed, falling back to speech synthesis');
                        speakAnnouncement(data);
                    });
            }
            
            function playAudioFromUrl(audioUrl) {
                console.log('=== PLAYING AUDIO FROM URL ===');
                console.log('Audio URL:', audioUrl);
                
                // Check if it's ResponsiveVoice
                if (audioUrl.startsWith('responsivevoice://')) {
                    console.log('Using ResponsiveVoice');
                    playResponsiveVoice(audioUrl);
                    return;
                }
                
                // Buat audio element baru untuk setiap pemanggilan
                const audioElement = new Audio(audioUrl);
                
                audioElement.oncanplaythrough = () => {
                    console.log('Audio ready to play');
                    audioElement.play().catch(error => {
                        console.error('Audio play error:', error);
                        // Fallback ke speech synthesis jika audio gagal
                        speakAnnouncement(data);
                    });
                };
                
                audioElement.onplay = () => {
                    console.log('Audio started playing');
                };
                
                audioElement.onended = () => {
                    console.log('Audio finished playing');
                };
                
                audioElement.onerror = (error) => {
                    console.error('Audio error:', error);
                    // Fallback ke speech synthesis jika audio gagal
                    speakAnnouncement(data);
                };
                
                // Load audio
                audioElement.load();
            }
            
            function playResponsiveVoice(audioUrl) {
                console.log('=== PLAYING RESPONSIVEVOICE ===');
                
                try {
                    // Decode text from URL
                    const encodedText = audioUrl.replace('responsivevoice://', '');
                    const text = atob(encodedText);
                    
                    console.log('ResponsiveVoice text:', text);
                    
                    // Check if ResponsiveVoice is loaded
                    if (typeof responsiveVoice !== 'undefined') {
                        console.log('ResponsiveVoice settings:', RESPONSIVEVOICE_CONFIG);
                        
                        responsiveVoice.speak(text, RESPONSIVEVOICE_CONFIG.voice, {
                            rate: RESPONSIVEVOICE_CONFIG.rate,
                            pitch: RESPONSIVEVOICE_CONFIG.pitch,
                            volume: RESPONSIVEVOICE_CONFIG.volume,
                            onstart: function() {
                                console.log('ResponsiveVoice started');
                            },
                            onend: function() {
                                console.log('ResponsiveVoice ended');
                            },
                            onerror: function(error) {
                                console.error('ResponsiveVoice error:', error);
                                // Fallback ke speech synthesis
                                speakAnnouncement(data);
                            }
                        });
                    } else {
                        console.error('ResponsiveVoice not loaded');
                        // Fallback ke speech synthesis
                        speakAnnouncement(data);
                    }
                } catch (error) {
                    console.error('ResponsiveVoice decode error:', error);
                    // Fallback ke speech synthesis
                speakAnnouncement(data);
                }
            }
            
            function speakAnnouncement(data) {
                console.log('=== SPEAKING ANNOUNCEMENT ===');
                console.log('Data:', data);
                console.log('Speech synthesis available:', 'speechSynthesis' in window);
                console.log('Audio enabled:', audioEnabled);
                
                if (!audioEnabled) {
                    console.log('Audio not enabled yet, enabling now...');
                    enableAudio();
                }
                
                if ('speechSynthesis' in window) {
                    const utterance = new SpeechSynthesisUtterance();
                    
                    // Handle undefined values dengan data yang lebih lengkap
                    // Ganti tanda minus dengan spasi agar tidak terbaca oleh audio
                    const queueNumber = (data.queueNumber || 'Tidak diketahui').replace(/-/g, ' ');
                    const serviceName = data.serviceName || 'Layanan';
                    const counterName = data.counterName || 'Loket';
                    const zona = data.zona || 'Zona';
                    
                    // Buat teks pemanggilan yang lebih informatif dan mudah dipahami
                    utterance.text = `Nomor antrian ${queueNumber}, untuk layanan ${serviceName}, silakan menuju ke ${counterName} di ${zona}. Terima kasih.`;
                    utterance.lang = 'id-ID';
                    utterance.rate = 0.8; // Kecepatan agak lambat
                    utterance.pitch = 1;
                    utterance.volume = 1;
                    
                    console.log('Utterance text:', utterance.text);
                    console.log('Utterance lang:', utterance.lang);
                    
                    // Coba gunakan voice Indonesia jika tersedia
                    const voices = speechSynthesis.getVoices();
                    console.log('Available voices:', voices.length);
                    
                    const indonesianVoice = voices.find(voice => 
                        voice.lang.includes('id') || voice.lang.includes('ID')
                    );
                    if (indonesianVoice) {
                        utterance.voice = indonesianVoice;
                        console.log('Using Indonesian voice:', indonesianVoice.name);
                    } else {
                        console.log('No Indonesian voice found, using default');
                    }
                    
                    // Event handlers
                    utterance.onstart = () => console.log('Speech started');
                    utterance.onend = () => console.log('Speech ended');
                    utterance.onerror = (e) => console.log('Speech error:', e.error);
                    
                    console.log('Starting speech synthesis...');
                    speechSynthesis.speak(utterance);
                } else {
                    console.log('Speech synthesis not supported');
                }
            }
        });
    </script>
</x-filament-panels::page>
