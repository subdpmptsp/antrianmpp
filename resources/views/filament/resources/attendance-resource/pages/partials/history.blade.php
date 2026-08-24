@php($history = $this->getHistoryPaginator())
<div class="space-y-4">
    <div class="attendance-panel p-4">
        <div class="attendance-controls">
            <div class="attendance-field"><label for="history-from">Dari tanggal</label><input id="history-from" type="date" wire:model.defer="historyFrom"></div>
            <div class="attendance-field"><label for="history-until">Sampai tanggal</label><input id="history-until" type="date" wire:model.defer="historyUntil"></div>
            <div class="attendance-field"><label for="history-instansi">Instansi</label><select id="history-instansi" wire:model.defer="historyInstansi"><option value="">Semua instansi</option>@foreach($instansiOptions as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
            <div class="attendance-field"><label for="history-status">Status</label><select id="history-status" wire:model.defer="historyStatus"><option value="all">Semua status</option><option value="present">Hadir</option><option value="absent">Tidak hadir</option><option value="unassigned">Instansi belum diatur</option></select></div>
            <div class="attendance-field"><label for="history-search">Pencarian</label><input id="history-search" type="search" wire:model.defer="historySearch" placeholder="Petugas/instansi"></div>
            <div class="flex gap-2"><x-filament::button wire:click="applyHistoryFilters" icon="heroicon-o-funnel">Terapkan</x-filament::button><x-filament::button wire:click="exportHistory" icon="heroicon-o-arrow-down-tray" color="success">Excel</x-filament::button></div>
        </div>
        @error('historyFrom')<p class="mt-2 text-sm text-danger-600">{{ $message }}</p>@enderror
        @error('historyUntil')<p class="mt-2 text-sm text-danger-600">{{ $message }}</p>@enderror
        <p class="mt-3 text-xs text-gray-500">Tampilan maksimal 92 hari agar tetap ringan. Export dapat memuat hingga 366 hari.</p>
    </div>

    <div class="attendance-panel">
        <div class="attendance-table-wrap">
            <table class="attendance-table">
                <thead><tr><th>Tanggal</th><th>Petugas</th><th>Instansi</th><th>Status</th><th>Jam Login</th></tr></thead>
                <tbody>
                    @forelse($history as $row)
                        <tr><td>{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td><td class="font-semibold">{{ $row['name'] }}</td><td>{{ $row['instansi'] }}</td><td><span class="attendance-badge attendance-badge--{{ $row['status'] }}">{{ $statusLabels[$row['status']]??$row['status'] }}</span></td><td>{{ $row['check_in']?$row['check_in'].' WIB':'-' }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="attendance-empty">Tidak ada data pada rentang dan filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($history->hasPages())<div class="border-t border-gray-200 p-4 dark:border-gray-700">{{ $history->links() }}</div>@endif
    </div>
</div>
