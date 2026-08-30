<x-filament::page>
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Monitoring Real Time</h2>
        <div class="flex items-center space-x-2">
            <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
            <span class="text-sm text-gray-600">Auto-refresh setiap 30 detik</span>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg shadow">
        <table class="min-w-full border text-center">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 border">Layanan</th>
                    <th class="px-4 py-2 border bg-yellow-100">Menunggu</th>
                    <th class="px-4 py-2 border bg-orange-100">Dipanggil</th>
                    <th class="px-4 py-2 border bg-blue-100">Dilayani</th>
                    <th class="px-4 py-2 border bg-green-100">Selesai</th>
                    <th class="px-4 py-2 border bg-red-100">Batal/Lewat</th>
                </tr>
            </thead>
            <tbody>
                @foreach($this->getMonitoringRealTime() as $service)
                    <tr class="bg-white-100 hover:bg-gray-50">
                        <td class="border px-4 py-2 text-left font-medium">{{ $service->name }}</td>
                        <td class="border px-4 py-2 font-bold text-yellow-600">{{ $service->menunggu_count }}</td>
                        <td class="border px-4 py-2 font-bold text-orange-600">{{ $service->dipanggil_count }}</td>
                        <td class="border px-4 py-2 font-bold text-blue-600">{{ $service->dilayani_count }}</td>
                        <td class="border px-4 py-2 font-bold text-green-600">{{ $service->selesai_count }}</td>
                        <td class="border px-4 py-2 font-bold text-red-600">{{ $service->batal_count }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- REKAP PER HARI --}}
    <div class="flex justify-between items-center my-6">
        <h2 class="text-xl font-bold">Rekap Per Hari</h2>
        <x-filament::button wire:click="exportExcel" color="success" icon="heroicon-o-arrow-down-tray">
            Export Excel
        </x-filament::button>
    </div>

    <div class="flex items-center space-x-4 mb-4">
        <div>
            <label for="monitoring-from-date" class="block text-sm font-medium">Dari Tanggal</label>
            <input type="date" id="monitoring-from-date" name="monitoring-from-date" wire:model="from" class="border p-2 rounded" aria-label="Dari Tanggal">
        </div>
        <div>
            <label for="monitoring-to-date" class="block text-sm font-medium">Sampai Tanggal</label>
            <input type="date" id="monitoring-to-date" name="monitoring-to-date" wire:model="to" class="border p-2 rounded" aria-label="Sampai Tanggal">
        </div>
    </div>
    
    <div class="overflow-x-auto rounded-lg shadow mt-4">
        <table class="w-full border">
            <thead class="bg-gray-100">
                <tr>
                    <th class="border p-2 text-left">Instansi / Layanan</th>
                    <th class="border p-2 text-center">Jumlah Pemohon</th>
                </tr>
            </thead>
            <tbody>
            @foreach($this->getRekapJumlahPemohon() as $instansi)
                @php $isExpanded = in_array($instansi->instansi_id, $expandedInstansiIds, true); @endphp
                <tr wire:click="toggleInstansi({{ $instansi->instansi_id }})" class="cursor-pointer bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/20 dark:hover:bg-blue-900/40">
                    <td class="border px-4 py-3 text-left font-bold text-blue-900 dark:text-blue-100 uppercase">
                        {{ $instansi->nama_instansi }}
                        <span class="ml-2 text-xs font-medium normal-case text-blue-600 dark:text-blue-300">{{ $isExpanded ? 'Sembunyikan layanan' : $instansi->services->count().' layanan' }}</span>
                    </td>
                    <td class="border px-4 py-3 text-center font-bold text-blue-700 dark:text-blue-200">Total: {{ $instansi->total_pemohon }}</td>
                </tr>
                @if($isExpanded)
                    @foreach($instansi->services as $service)
                        <tr class="bg-white hover:bg-gray-50 dark:bg-gray-800 dark:hover:bg-gray-700">
                            <td class="border px-4 py-2 pl-8 text-left text-gray-700 dark:text-gray-200">
                                <span class="mr-2 text-blue-500">↳</span>{{ $service->prefix }} — {{ $service->name }}
                            </td>
                            <td class="border px-4 py-2 text-center font-semibold text-gray-700 dark:text-gray-200">{{ $service->total_pemohon }}</td>
                        </tr>
                    @endforeach
                @endif
            @endforeach
            </tbody>
        </table>
    </div>

    @push('scripts')
    <script>
        // Auto-refresh monitoring real-time setiap 30 detik
        setInterval(function() {
            Livewire.emit('refreshMonitoring');
        }, 30000);
        
        // Refresh saat halaman difokus
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                Livewire.emit('refreshMonitoring');
            }
        });
    </script>
    @endpush
</x-filament::page>
