@php($recap = $this->getMonthlyRecap())
<div class="space-y-4">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div class="attendance-field" style="max-width:12rem"><label for="recap-year">Tahun rekap</label><input id="recap-year" type="number" min="2020" max="{{ now()->year }}" wire:model.live.debounce.500ms="recapYear"></div>
        <x-filament::button wire:click="exportYearlyRecap" icon="heroicon-o-arrow-down-tray" color="success">Export Rekap Excel</x-filament::button>
    </div>
    <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">Persentase = hari instansi terwakili ÷ hari kerja efektif. Akhir pekan, hari libur, dan tanggal masa depan tidak dihitung.</div>
    <div class="attendance-panel attendance-table-wrap">
        <table class="attendance-table">
            <thead><tr><th>Instansi</th><th>Pola</th>@foreach($recap['months'] as $month)<th>{{ $month }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($recap['instansis'] as $instansi)
                    <tr>
                        <td class="font-semibold" style="min-width:18rem">{{ $instansi['nama_instansi'] }}</td><td>{{ $instansi['work_days_per_week'] }} hari</td>
                        @foreach($recap['months'] as $monthNumber=>$month)
                            @php($monthData=$instansi['months'][$monthNumber]) @php($percentage=$monthData['percentage']??null)
                            <td class="attendance-recap-cell {{ $percentage===null?'is-future':($percentage>=90?'is-good':($percentage>=70?'is-warning':'is-danger')) }}" title="{{ $monthData?$monthData['days_present'].' dari '.$monthData['total_days'].' hari kerja':'Belum berjalan' }}">{{ $percentage===null?'–':$percentage.'%' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="14" class="attendance-empty">Belum ada instansi yang mempunyai akun petugas aktif.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
