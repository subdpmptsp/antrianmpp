@php
    $recap = $this->getMonthlyRecap();
    $yearlyRecap = $this->getYearlyRecap();
    $zoneOptions = $this->getZoneOptions();
    $monthOptions = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $chartData = [
        'labels' => $recap['days']->pluck('label')->all(),
        'datasets' => [
            [
                'label' => 'Hadir',
                'data' => $recap['days']->pluck('present')->all(),
                'backgroundColor' => '#16a34a',
                'borderColor' => '#16a34a',
                'borderWidth' => 0,
                'borderRadius' => 3,
                'stack' => 'current',
                'order' => 2,
            ],
            [
                'label' => 'Tidak hadir',
                'data' => $recap['days']->pluck('absent')->all(),
                'backgroundColor' => '#ef4444',
                'borderColor' => '#ef4444',
                'borderWidth' => 0,
                'borderRadius' => 3,
                'stack' => 'current',
                'order' => 2,
            ],
            [
                'type' => 'line',
                'label' => 'Hadir · '.$recap['previous_month_label'],
                'data' => $recap['days']->pluck('previous_present')->all(),
                'borderColor' => '#94a3b8',
                'backgroundColor' => 'transparent',
                'borderWidth' => 2,
                'borderDash' => [6, 5],
                'pointRadius' => 2,
                'pointHoverRadius' => 5,
                'tension' => 0.2,
                'fill' => false,
                'stack' => 'previous',
                'order' => 1,
            ],
        ],
    ];
    $chartOptions = [
        'responsive' => true,
        'maintainAspectRatio' => false,
        'interaction' => ['mode' => 'index', 'intersect' => false],
        'plugins' => [
            'legend' => [
                'display' => true,
                'position' => 'top',
                'align' => 'end',
                'labels' => ['usePointStyle' => true, 'boxWidth' => 8, 'boxHeight' => 8],
            ],
        ],
        'scales' => [
            'x' => [
                'stacked' => true,
                'grid' => ['display' => false],
                'title' => ['display' => true, 'text' => 'Tanggal'],
                'ticks' => ['autoSkip' => true, 'maxTicksLimit' => 16],
            ],
            'y' => [
                'stacked' => true,
                'beginAtZero' => true,
                'ticks' => ['precision' => 0],
                'title' => ['display' => true, 'text' => 'Jumlah petugas'],
            ],
        ],
    ];
    $chartKey = md5(json_encode([$recapYear, $recapMonth, $recapZone, $recapInstansi, $recapWorkPattern, $chartData]));
    $maximumAbsentDays = max(1, (int) $recap['ranking']->max('absent_days'));
@endphp

<div class="space-y-4">
    <div class="attendance-monthly-toolbar">
        <div class="attendance-field">
            <label for="recap-month">Bulan</label>
            <select id="recap-month" wire:model.live="recapMonth">
                @foreach($monthOptions as $number => $month)
                    <option value="{{ $number }}" @disabled($recapYear === now()->year && $number > now()->month)>{{ $month }}</option>
                @endforeach
            </select>
            @error('recapMonth')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="attendance-field">
            <label for="recap-year">Tahun</label>
            <select id="recap-year" wire:model.live="recapYear">
                @foreach(range(now()->year, 2020) as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
        <div class="attendance-field">
            <label for="recap-zone">Zona</label>
            <select id="recap-zone" wire:model.live="recapZone">
                <option value="all">Semua zona</option>
                @foreach($zoneOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="attendance-field">
            <label for="recap-instansi">Instansi</label>
            <select id="recap-instansi" wire:model.live="recapInstansi">
                <option value="">Semua instansi</option>
                @foreach($instansiOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
            </select>
        </div>
        <div class="attendance-field">
            <label for="recap-work-pattern">Pola kerja</label>
            <select id="recap-work-pattern" wire:model.live="recapWorkPattern">
                <option value="all">Semua pola kerja</option>
                <option value="5">5 hari · Senin–Jumat</option>
                <option value="6">6 hari · Senin–Sabtu</option>
            </select>
        </div>
        <x-filament::button wire:click="exportMonthlyRecap" wire:loading.attr="disabled" icon="heroicon-o-arrow-down-tray" color="success">
            Ekspor Excel
        </x-filament::button>
    </div>

    <div class="rounded-xl border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-100">
        Persentase ketidakhadiran instansi dihitung dari hari kerja ketika tidak ada satu pun petugas instansi yang hadir. Akhir pekan, hari libur, tanggal sebelum akun petugas dibuat, dan tanggal masa depan tidak dihitung.
    </div>

    <div class="attendance-monthly-grid">
        <section class="attendance-panel">
            <div class="attendance-panel__head">
                <div>
                    <h3 class="attendance-panel__title">Kehadiran Petugas per Hari</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Komposisi selama {{ $recap['month_label'] }} · {{ $recap['work_pattern_label'] }}.</p>
                </div>
            </div>
            <div class="attendance-chart-summary">
                <div><span>Hadir</span><strong class="is-present">{{ $recap['total_present'] }}</strong></div>
                <div><span>Tidak hadir</span><strong class="is-absent">{{ $recap['total_absent'] }}</strong></div>
            </div>
            @if($recap['total_expected'] === 0)
                <div class="mx-4 mt-4 rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                    Tidak ada petugas aktif yang terjadwal pada periode dan kombinasi filter ini.
                </div>
            @elseif(! $recap['has_attendance_data'])
                <div class="mx-4 mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-100">
                    Belum ada petugas yang tercatat hadir pada periode dan filter ini. Batang merah tetap menunjukkan petugas yang dijadwalkan tetapi belum tercatat hadir.
                </div>
            @endif
            <div id="attendance-monthly-chart-{{ $chartKey }}" class="p-4" wire:key="attendance-monthly-chart-{{ $chartKey }}">
                <div
                    x-load
                    x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                    x-data="chart({ cachedData: @js($chartData), options: @js($chartOptions), type: 'bar' })"
                    wire:key="attendance-monthly-chart-instance-{{ $chartKey }}"
                    wire:ignore
                    class="fi-color-primary"
                >
                    <div style="height: 330px; position: relative;">
                        <canvas x-ref="canvas" role="img" aria-label="Grafik kehadiran dan ketidakhadiran petugas per tanggal"></canvas>
                    </div>
                    <span x-ref="backgroundColorElement" class="text-primary-50 dark:text-primary-400/10"></span>
                    <span x-ref="borderColorElement" class="text-primary-600 dark:text-primary-400"></span>
                    <span x-ref="gridColorElement" class="text-gray-200 dark:text-gray-800"></span>
                    <span x-ref="textColorElement" class="text-gray-500 dark:text-gray-400"></span>
                </div>
            </div>
        </section>

        <section class="attendance-panel">
            <div class="attendance-panel__head">
                <div>
                    <h3 class="attendance-panel__title">Ketidakhadiran Tertinggi</h3>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Hari instansi tidak terwakili pada filter aktif.</p>
                </div>
            </div>
            <div class="attendance-ranking-list">
                @forelse($recap['ranking'] as $index => $row)
                    <div class="attendance-ranking-item">
                        <span class="attendance-ranking-number">{{ $index + 1 }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <span class="attendance-ranking-name" title="{{ $row['name'] }}">{{ $row['name'] }}</span>
                                <span class="attendance-ranking-value">{{ $row['absent_days'] }} hari · {{ $row['percentage'] }}%</span>
                            </div>
                            <div class="attendance-ranking-track"><span style="width: {{ max(3, ($row['absent_days'] / $maximumAbsentDays) * 100) }}%"></span></div>
                        </div>
                    </div>
                @empty
                    <div class="attendance-empty">Tidak ada ketidakhadiran instansi pada filter ini.</div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="attendance-panel">
        <div class="attendance-panel__head">
            <div>
                <h3 class="attendance-panel__title">Petugas dengan Pola Tidak Hadir Berulang</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Petugas dengan minimal dua hari tidak hadir dalam bulan terpilih.</p>
            </div>
        </div>
        <div class="attendance-table-wrap">
            <table class="attendance-table">
                <thead><tr><th>Petugas</th><th>Instansi</th><th>Zona</th><th>Tanggal Tidak Hadir</th><th>Total</th><th>Pola</th></tr></thead>
                <tbody>
                    @forelse($recap['repeated_absences'] as $row)
                        <tr>
                            <td class="font-semibold">{{ $row['name'] }}</td>
                            <td>{{ $row['instansi'] }}</td>
                            <td><span class="attendance-badge">{{ $row['zone'] }}</span></td>
                            <td>{{ implode(', ', $row['dates']) }}</td>
                            <td class="font-semibold text-red-700 dark:text-red-300">{{ $row['total'] }} hari</td>
                            <td>{{ $row['pattern'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="attendance-empty">Tidak ada petugas dengan pola tidak hadir berulang pada filter ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="attendance-panel">
        <div class="attendance-panel__head">
            <div>
                <h3 class="attendance-panel__title">Rekap Persentase Tahunan per Instansi</h3>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Persentase keterwakilan instansi setiap bulan pada tahun {{ $recapYear }}.</p>
            </div>
            <x-filament::button wire:click="exportYearlyRecap" wire:loading.attr="disabled" icon="heroicon-o-arrow-down-tray" color="success">
                Ekspor Rekap Tahunan
            </x-filament::button>
        </div>
        <div class="border-b border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-100">
            Persentase = hari instansi terwakili ÷ hari kerja efektif. Akhir pekan, hari libur, dan tanggal masa depan tidak dihitung.
        </div>
        <div class="attendance-table-wrap">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>Instansi</th>
                        <th>Pola</th>
                        @foreach($yearlyRecap['months'] as $month)<th>{{ $month }}</th>@endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($yearlyRecap['instansis'] as $instansi)
                        <tr>
                            <td class="font-semibold" style="min-width:18rem">{{ $instansi['nama_instansi'] }}</td>
                            <td>{{ $instansi['work_days_per_week'] }} hari</td>
                            @foreach($yearlyRecap['months'] as $monthNumber => $month)
                                @php($monthData = $instansi['months'][$monthNumber])
                                @php($percentage = $monthData['percentage'] ?? null)
                                <td
                                    class="attendance-recap-cell {{ $percentage === null ? 'is-future' : ($percentage >= 90 ? 'is-good' : ($percentage >= 70 ? 'is-warning' : 'is-danger')) }}"
                                    title="{{ $monthData ? $monthData['days_present'].' dari '.$monthData['total_days'].' hari kerja' : 'Belum berjalan' }}"
                                >{{ $percentage === null ? '–' : $percentage.'%' }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="14" class="attendance-empty">Belum ada instansi yang mempunyai akun petugas aktif.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
