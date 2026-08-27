<x-filament-panels::page>
    <div wire:poll.10s="refreshData" class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Monitoring Real-Time</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Diperbarui {{ now()->setTimestamp($lastRefreshedAt)->format('H:i:s') }}
                </p>
            </div>
            <a href="{{ $exportUrl }}"
               class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-primary-500">
                Export Excel
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                'Total Hari Ini' => $summary['total'],
                'Menunggu' => $summary['menunggu'],
                'Sedang Dilayani' => $summary['sedang_dilayani'],
                'Selesai' => $summary['selesai'],
                'Rata-rata Tunggu' => $summary['avg_wait_minutes'] !== null ? $summary['avg_wait_minutes'] . ' menit' : '-',
            ] as $label => $value)
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold text-gray-950 dark:text-white">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <h2 class="mb-4 text-lg font-semibold text-gray-950 dark:text-white">Kepadatan Zona</h2>
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($zones as $zone)
                    <div class="rounded-lg border p-4 {{ $zone['is_padat'] ? 'border-danger-300 bg-danger-50 dark:border-danger-700 dark:bg-danger-950/30' : 'border-gray-200 dark:border-gray-700' }}">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-semibold text-gray-950 dark:text-white">{{ $zone['name'] }}</span>
                            <span class="text-sm {{ $zone['is_padat'] ? 'text-danger-700 dark:text-danger-300' : 'text-gray-500' }}">
                                {{ $zone['is_padat'] ? 'Padat' : 'Normal' }}
                            </span>
                        </div>
                        <div class="mt-3 flex gap-4 text-sm text-gray-600 dark:text-gray-300">
                            <span>Menunggu: {{ $zone['menunggu'] }}</span>
                            <span>Dilayani: {{ $zone['dilayani'] }}</span>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div class="h-full rounded-full bg-primary-500" style="width: {{ $zone['progress'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">Belum ada zona yang terkonfigurasi.</p>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="grid gap-4 border-b border-gray-200 p-5 dark:border-gray-700 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-600 dark:text-gray-300">Zona</label>
                    <select wire:model.live="zoneFilter" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        <option value="">Semua zona</option>
                        @foreach ($zoneOptions as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Pilih zona untuk membatasi data layanan agar tampilan lebih ringan.
                    </p>
                </div>
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-600 dark:text-gray-300">Cari layanan</label>
                    <input wire:model.live.debounce.500ms="search" type="search" placeholder="Cari layanan..."
                           class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        <tr>
                            <th class="px-4 py-3">Layanan</th>
                            <th class="px-4 py-3">Instansi</th>
                            <th class="px-4 py-3 text-center">Menunggu</th>
                            <th class="px-4 py-3 text-center">Dipanggil</th>
                            <th class="px-4 py-3 text-center">Dilayani</th>
                            <th class="px-4 py-3 text-center">Selesai</th>
                            <th class="px-4 py-3 text-center">Batal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($services as $service)
                            <tr wire:key="monitoring-service-{{ $service->id }}">
                                <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $service->name }}</td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $service->instansi?->nama_instansi ?? '-' }}</td>
                                <td class="px-4 py-3 text-center">{{ $service->menunggu_count }}</td>
                                <td class="px-4 py-3 text-center">{{ $service->dipanggil_count }}</td>
                                <td class="px-4 py-3 text-center">{{ $service->dilayani_count }}</td>
                                <td class="px-4 py-3 text-center">{{ $service->selesai_count }}</td>
                                <td class="px-4 py-3 text-center">{{ $service->batal_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Tidak ada layanan yang sesuai.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
