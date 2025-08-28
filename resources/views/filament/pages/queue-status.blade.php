<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-indigo-50" wire:poll.5000ms="loadData">
    <!-- Header dengan glassmorphism effect -->
    <div class="relative pt-8 pb-6">
        <div class="absolute inset-0 bg-white/30 backdrop-blur-sm border-b border-white/20"></div>
        <div class="relative container mx-auto px-4 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl shadow-lg mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-2">
                Status Antrian
            </h1>
            <p class="text-lg text-gray-600 font-medium">{{ $queue->service->name }}</p>
            <p class="text-sm text-gray-500 mt-1">{{ now()->format('d M Y H:i') }}</p>
        </div>
    </div>

    <div class="container mx-auto px-4 pb-8 max-w-2xl">
        <!-- Kartu Nomor Antrian dengan animasi -->
        <div class="bg-white/70 backdrop-blur-md rounded-3xl shadow-xl border border-white/20 p-8 mb-8 hover:shadow-2xl transition-all duration-300">
            <div class="text-center mb-6">
                <h2 class="text-lg font-semibold text-gray-700 mb-4">Nomor Antrian Anda</h2>
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-indigo-500 rounded-2xl blur opacity-20"></div>
                    <div class="relative bg-gradient-to-r from-blue-500 to-indigo-600 rounded-2xl p-6 text-white shadow-lg">
                        <div class="text-6xl font-bold mb-2">{{ $queue->number }}</div>
                        <div class="inline-flex items-center px-4 py-2 rounded-full bg-white/20 backdrop-blur-sm">
                            <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                            <span class="text-sm font-medium">{{ $this->getStatusLabel() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($queue->counter && $queue->called_at && !$queue->served_at)
            <div class="bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-200 rounded-2xl p-6 mt-6">
                <div class="flex items-center gap-4">
                    <div class="flex-shrink-0">
                        <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div>
                        <p class="text-emerald-800 font-semibold">Silakan menuju ke</p>
                        <p class="text-emerald-900 font-bold text-lg">{{ $queue->counter->name }}</p>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Antrian Sedang Dilayani -->
        @if(!$queue->finished_at)
        <div class="bg-white/70 backdrop-blur-md rounded-3xl shadow-xl border border-white/20 p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-800">Antrian Sedang Dilayani</h2>
                @if ($waitingCount)
                <div class="bg-orange-100 text-orange-800 px-4 py-2 rounded-full text-sm font-medium">
                    {{ $waitingCount }} antrian di depan Anda
                </div>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @forelse ($currentQueues as $currentQueue)
                <div class="group relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-400 to-indigo-500 rounded-2xl blur opacity-0 group-hover:opacity-20 transition-opacity duration-300"></div>
                    <div class="relative bg-white/80 backdrop-blur-sm border border-gray-200 rounded-2xl p-6 text-center hover:shadow-lg transition-all duration-300">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600 mb-2">{{ $currentQueue->counter->name }}</p>
                        <p class="text-2xl font-bold text-blue-600">{{ $currentQueue->number }}</p>
                        <div class="mt-3 flex items-center justify-center">
                            <div class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></div>
                            <span class="ml-2 text-xs text-gray-500">Sedang dilayani</span>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <p class="text-gray-500">Belum ada antrian yang sedang dilayani</p>
                </div>
                @endforelse
            </div>
        </div>
        @endif

        <!-- Footer dengan auto-refresh indicator -->
        <div class="text-center mt-8 p-4">
            <div class="inline-flex items-center px-4 py-2 bg-white/60 backdrop-blur-sm rounded-full border border-white/20">
                <div class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></div>
                <span class="text-sm text-gray-600">Memperbarui otomatis setiap 5 detik</span>
            </div>
        </div>
    </div>
</div>