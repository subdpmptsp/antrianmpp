<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Monitoring</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pantau antrean hari ini atau buka rekap layanan berdasarkan periode.</p>
            </div>
            @if ($activeTab === 'realtime')
                <x-filament::button wire:click="refreshData" icon="heroicon-o-arrow-path" color="gray">Refresh Data</x-filament::button>
            @endif
        </div>

        <div class="inline-flex rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
            <button type="button" wire:click="selectTab('realtime')" class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'realtime' ? 'bg-primary-600 text-white shadow-sm hover:bg-primary-500' : 'text-gray-600 dark:text-gray-300' }}">Pantauan Hari Ini</button>
            <button type="button" wire:click="selectTab('report')" class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $activeTab === 'report' ? 'bg-primary-600 text-white shadow-sm hover:bg-primary-500' : 'text-gray-600 dark:text-gray-300' }}">Rekap &amp; Export</button>
        </div>

        @if ($activeTab === 'realtime')
            <p class="text-sm text-gray-500 dark:text-gray-400">Diperbarui {{ now()->setTimestamp($lastRefreshedAt)->format('H:i:s') }}. Detail layanan hanya dimuat setelah zona dipilih.</p>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
                @foreach ([
                    ['Total Hari Ini', $summary['total'], 'text-primary-600'],
                    ['Menunggu', $summary['menunggu'], 'text-amber-600'],
                    ['Sedang Dilayani', $summary['sedang_dilayani'], 'text-sky-600'],
                    ['Selesai', $summary['selesai'], 'text-emerald-600'],
                    ['Batal / Lewat', $summary['batal'], 'text-rose-600'],
                    ['Rata-rata Tunggu', $summary['avg_wait_minutes'] !== null ? $summary['avg_wait_minutes'].' menit' : '-', 'text-violet-600'],
                ] as [$label, $value, $color])
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p><p class="mt-2 text-2xl font-bold {{ $color }}">{{ $value }}</p></div>
                @endforeach
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="mb-4 text-lg font-semibold text-gray-950 dark:text-white">Kepadatan Zona</h2>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    @foreach ($zones as $zone)
                        <button type="button" wire:click="$set('zoneFilter', '{{ $zone['id'] }}')" class="rounded-lg border p-4 text-left transition {{ (string) $zoneFilter === (string) $zone['id'] ? 'border-primary-500 bg-primary-50 ring-2 ring-primary-100 dark:bg-primary-950/30' : ($zone['is_padat'] ? 'border-danger-300 bg-danger-50 dark:border-danger-700 dark:bg-danger-950/30' : 'border-gray-200 dark:border-gray-700') }}">
                            <div class="flex items-center justify-between gap-3"><span class="font-semibold text-gray-950 dark:text-white">{{ $zone['name'] }}</span><span class="text-xs {{ $zone['is_padat'] ? 'text-danger-700 dark:text-danger-300' : 'text-gray-500' }}">{{ $zone['is_padat'] ? 'Padat' : 'Normal' }}</span></div>
                            <div class="mt-3 flex gap-3 text-sm text-gray-600 dark:text-gray-300"><span>Menunggu: {{ $zone['menunggu'] }}</span><span>Dilayani: {{ $zone['dilayani'] }}</span></div>
                        </button>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-4 border-b border-gray-200 p-5 dark:border-gray-700 md:grid-cols-2">
                    <div><label class="mb-2 block text-sm font-medium text-gray-600 dark:text-gray-300">Zona yang dipantau</label><select wire:model.live="zoneFilter" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"><option value="">Pilih zona untuk melihat detail</option>@foreach ($zoneOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
                    <div><label class="mb-2 block text-sm font-medium text-gray-600 dark:text-gray-300">Cari layanan</label><input wire:model.live.debounce.500ms="search" type="search" placeholder="Cari layanan pada zona terpilih..." class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900" @disabled(! $zoneFilter)></div>
                </div>
                @if (! $zoneFilter)
                    <p class="px-5 py-12 text-center text-sm text-gray-500">Pilih zona terlebih dahulu. Sistem belum memuat daftar layanan agar halaman tetap ringan.</p>
                @else
                    <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="text-xs uppercase"><tr><th class="bg-gray-50 px-4 py-3 text-gray-600 dark:bg-gray-800 dark:text-gray-300">Layanan</th><th class="bg-gray-50 px-4 py-3 text-gray-600 dark:bg-gray-800 dark:text-gray-300">Instansi</th><th class="bg-amber-100 px-4 py-3 text-center text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">Menunggu</th><th class="bg-orange-100 px-4 py-3 text-center text-orange-800 dark:bg-orange-950/50 dark:text-orange-300">Dipanggil</th><th class="bg-sky-100 px-4 py-3 text-center text-sky-800 dark:bg-sky-950/50 dark:text-sky-300">Dilayani</th><th class="bg-emerald-100 px-4 py-3 text-center text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">Selesai</th><th class="bg-rose-100 px-4 py-3 text-center text-rose-800 dark:bg-rose-950/50 dark:text-rose-300">Batal</th></tr></thead><tbody class="divide-y divide-gray-200 dark:divide-gray-700">@forelse ($services as $service)<tr wire:key="monitoring-service-{{ $service->id }}"><td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $service->name }}</td><td class="px-4 py-3 text-gray-600 dark:text-gray-300">{{ $service->instansi?->nama_instansi ?? '-' }}</td><td class="px-4 py-3 text-center">{{ $service->menunggu_count }}</td><td class="px-4 py-3 text-center">{{ $service->dipanggil_count }}</td><td class="px-4 py-3 text-center">{{ $service->dilayani_count }}</td><td class="px-4 py-3 text-center">{{ $service->selesai_count }}</td><td class="px-4 py-3 text-center">{{ $service->batal_count }}</td></tr>@empty<tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Tidak ada layanan yang sesuai.</td></tr>@endforelse</tbody></table></div>
                @endif
            </div>
        @else
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="grid gap-5 lg:grid-cols-[1fr_1fr_auto] lg:items-end"><div>{{ $this->form }}</div><div><label class="mb-2 block text-sm font-medium text-gray-600 dark:text-gray-300">Zona rekap <span class="text-danger-600">*</span></label><select wire:model="reportZoneFilter" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900"><option value="">Pilih zona terlebih dahulu</option><option value="all">Semua Zona (Rekap Gabungan)</option>@foreach ($zoneOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div><div class="flex flex-wrap gap-2"><x-filament::button wire:click="applyReportFilters" icon="heroicon-o-funnel">Terapkan Rekap</x-filament::button><x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-arrow-down-tray" title="{{ $this->exportDescription() }}">Export Excel</x-filament::button></div></div>
                <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">{{ $this->exportDescription() }}</p>
            </div>
            @if (! $reportZoneFilter)
                <div class="rounded-xl bg-white px-5 py-12 text-center text-sm text-gray-500 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">Pilih zona terlebih dahulu. Rekap rinci belum dihitung agar halaman tetap cepat.</div>
            @else
                <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            <tr><th class="px-4 py-3 text-left">Instansi / Layanan</th><th class="px-4 py-3 text-center">Jumlah Pemohon</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse ($rekapan as $instansi)
                                @php($isExpanded = in_array($instansi->instansi_id, $expandedInstansiIds, true))
                                <tr wire:click="toggleInstansi({{ $instansi->instansi_id }})" class="cursor-pointer bg-primary-50 hover:bg-primary-100 dark:bg-primary-950/20">
                                    <td class="px-4 py-3 font-bold text-primary-900 dark:text-primary-100">{{ $instansi->nama_instansi }} <span class="ml-2 text-xs font-medium">{{ $isExpanded ? 'Sembunyikan layanan' : $instansi->services->count().' layanan' }}</span></td>
                                    <td class="px-4 py-3 text-center font-bold text-primary-700 dark:text-primary-200">{{ $instansi->total_pemohon }}</td>
                                </tr>
                                @if ($isExpanded)
                                    @foreach ($instansi->services as $service)
                                        <tr><td class="px-4 py-2 pl-8 text-gray-700 dark:text-gray-200">↳ {{ $service->prefix }} — {{ $service->name }}</td><td class="px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">{{ $service->total_pemohon }}</td></tr>
                                    @endforeach
                                @endif
                            @empty
                                <tr><td colspan="2" class="px-4 py-8 text-center text-gray-500">Belum ada layanan aktif pada zona ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
