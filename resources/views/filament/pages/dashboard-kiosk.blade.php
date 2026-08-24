<div class="flex flex-col flex-grow p-4 lg:p-8 relative overflow-hidden" wire:poll.2s="refreshData">
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
                   <!-- <img src="{{asset('storage/'.$setting->image)}}" alt=""> -->
                </div>
                <div>
                    <h1 class="text-xl md:text-3xl font-bold text-gray-800">{{ $setting->name }}</h1>
                    <p class="text-gray-600">{{ $setting->address }}</p>
                </div>
            </div>
        </div>
    </div>
    @php
        $serviceGroups = $counters->groupBy('service.name')->filter(fn ($group, $name) => filled($name));
        $primaryGroup = $serviceGroups->first();
        $primaryCounter = $primaryGroup?->first();
        $primaryActiveQueue = $primaryCounter?->activeQueue;
        $primaryNextQueue = $primaryCounter?->nextQueue;
        $totalQueueCount = $primaryGroup ? $primaryGroup->sum('today_queue_count') : 0;
        $waitingQueueCount = $primaryGroup ? $primaryGroup->sum('waiting_queue_count') : 0;
        $nextQueueNumber = $primaryActiveQueue?->number ?? $primaryNextQueue?->number ?? '-';
        $nextQueueLabel = $primaryActiveQueue ? 'Sedang dipanggil' : ($primaryNextQueue ? 'Antrian berikutnya' : 'Belum ada antrian');
    @endphp

    <div class="relative grid grid-cols-1 gap-6 xl:items-start" style="grid-template-columns: minmax(0, 1.25fr) minmax(0, 1fr);">
        <section class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 text-center">
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-gray-500">Sedang Dipanggil</p>
                <h2 class="mt-1 text-2xl font-black text-gray-800">Nomor Antrean</h2>
            </div>

            <div class="px-6 py-6 text-center">
                <div class="mx-auto w-full max-w-md rounded-3xl border-2 border-orange-400 bg-orange-50/40 px-6 py-4">
                    <div class="text-6xl md:text-7xl font-black tracking-wider text-gray-900 leading-none">
                        {{ $primaryActiveQueue?->number ?? $primaryNextQueue?->number ?? '—' }}
                    </div>
                </div>

                <div class="mt-5 text-gray-600">
                    <span class="block text-sm font-semibold uppercase tracking-[0.18em] text-gray-500">Waktu Layanan</span>
                    <strong class="block mt-1 text-3xl font-black text-blue-700">
                        {{ $primaryActiveQueue ? now()->diff($primaryActiveQueue->called_at)->format('%H:%I:%S') : '00:00:00' }}
                    </strong>
                </div>
            </div>

            <div class="px-6 pb-5">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 text-center">
                        <span class="block text-sm text-gray-500">Total Antrean Layanan Ini</span>
                        <strong class="block mt-1 text-3xl font-black text-orange-500">{{ $totalQueueCount }}</strong>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 text-center">
                        <span class="block text-sm text-gray-500">{{ $nextQueueLabel }}</span>
                        <strong class="block mt-1 text-3xl font-black text-emerald-600">{{ $nextQueueNumber }}</strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <div>
                    <p class="text-sm text-gray-500 font-medium">{{ $primaryCounter?->service?->name ?? 'Layanan utama' }}</p>
                    <h3 class="text-xl font-black text-gray-800">{{ $waitingQueueCount }} Orang Dalam Antrian</h3>
                </div>
                <span class="inline-flex items-center gap-2 rounded-full bg-blue-100 px-4 py-2 text-sm font-bold text-blue-700">
                    <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                    Antrian Berjalan
                </span>
            </div>

            <div class="p-5 space-y-2">
                @forelse ($counters as $counter)
                    @php
                        $displayQueue = $counter->activeQueue ?? $counter->nextQueue;
                    @endphp
                    <div class="flex items-center justify-between gap-4 rounded-2xl border border-gray-200 bg-white px-4 py-3">
                        <div>
                            <strong class="block text-lg font-black text-gray-900">{{ $displayQueue?->number ?? '—' }}</strong>
                            <span class="block text-sm text-gray-500">{{ $counter->service?->name ?? 'Tidak ada layanan' }}</span>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-500">
                            {{ $counter->is_active ? ($displayQueue ? 'Aktif' : 'Menunggu') : 'Tutup' }}
                        </span>
                    </div>
                @empty
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-6 py-12 text-center">
                        <p class="text-lg font-semibold text-gray-600">Tidak ada counter</p>
                        <p class="mt-1 text-sm text-gray-400">Belum ada counter yang terdaftar.</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>

    <div class="mt-5 rounded-2xl border border-gray-200 bg-white px-6 py-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="block text-gray-500">Status Operasional</span>
                <strong class="block mt-1 text-gray-900">Siap dipantau</strong>
            </div>
            <div>
                <span class="block text-gray-500">Zona Aktif</span>
                <strong class="block mt-1 text-gray-900">{{ $counters->where('is_active', true)->count() }} Loket</strong>
            </div>
            <div>
                <span class="block text-gray-500">Antrean Hari Ini</span>
                <strong class="block mt-1 text-gray-900">{{ $counters->sum('today_queue_count') }} Nomor</strong>
            </div>
        </div>
    </div>

</div>

@push('styles')
    <style>
        /* Counter Card Consistent Styling untuk Semua Zona */
        .counter-card-consistent {
            min-height: 400px;
            display: flex;
            flex-direction: column;
            border-radius: 1rem; /* rounded-2xl = 1rem */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-lg */
            border-width: 1px;
            border-color: rgba(229, 231, 235, 0.5); /* border-gray-200/50 */
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .counter-card-consistent:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); /* shadow-2xl */
        }

        /* Pastikan semua card memiliki tinggi yang sama */
        .counter-card-consistent .relative.flex.flex-col.h-full {
            min-height: 400px;
        }

        /* Header konsisten untuk semua card */
        .counter-card-consistent .flex.justify-between.items-center {
            min-height: 80px;
        }

        /* Content area konsisten */
        .counter-card-consistent .flex-grow {
            min-height: 200px;
        }

        /* Footer konsisten */
        .counter-card-consistent .bg-gradient-to-r.from-gray-50 {
            min-height: 70px;
        }

        /* Padding dan spacing konsisten untuk semua card */
        .counter-card-consistent .flex.justify-between.items-center {
            padding: 1.5rem !important;
            min-height: 80px;
        }

        .counter-card-consistent .flex-grow.flex.flex-col.items-center.justify-center {
            padding: 2rem !important;
            min-height: 200px;
        }

        .counter-card-consistent .bg-gradient-to-r.from-gray-50 {
            padding: 1rem !important;
            min-height: 70px;
        }

        /* Memastikan semua text memiliki font size yang konsisten */
        .counter-card-consistent h2 {
            font-size: 1.875rem; /* text-3xl */
            font-weight: 700;
            line-height: 1.2;
        }

        /* Memastikan semua badge memiliki styling yang sama */
        .counter-card-consistent .px-3.py-1 {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px; /* rounded-full */
            font-size: 0.875rem; /* text-sm */
            font-weight: 600;
        }

        /* Memastikan semua card memiliki lebar yang sama di grid */
        .counter-card-consistent {
            width: 100%;
        }

        /* Responsive Design Konsisten untuk Semua Card */
        @media (max-width: 1536px) {
            .counter-card-consistent {
                min-height: 380px;
            }
        }

        @media (max-width: 1280px) {
            .counter-card-consistent {
                min-height: 360px;
            }
        }

        @media (max-width: 1024px) {
            .counter-card-consistent {
                min-height: 340px;
            }
        }

        @media (max-width: 768px) {
            .counter-card-consistent {
                min-height: 320px;
            }
        }

        @media (max-width: 640px) {
            .counter-card-consistent {
                min-height: 300px;
            }
        }

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
