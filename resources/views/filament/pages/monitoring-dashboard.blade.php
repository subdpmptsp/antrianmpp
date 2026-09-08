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

            @php
                $chartPoints = collect($queueAnalysis['points']);
                $peakChartLabel = $queueAnalysis['peak_label']
                    ? str_replace(':', '.', $queueAnalysis['peak_label'])
                    : null;
                $cursorChartPoint = $chartPoints->get(1) ?? $chartPoints->first();
                $cursorChartLabel = $cursorChartPoint
                    ? str_replace(':', '.', $cursorChartPoint['label'])
                    : '07.30';
                $averageChartTotal = $chartPoints->isNotEmpty()
                    ? (int) round($chartPoints->avg('total'))
                    : 0;
                $chartMaximum = max(
                    100,
                    (int) ceil(((int) $chartPoints->max('total') + 1) / 100) * 100,
                );
                $chartData = [
                    'labels' => $chartPoints->pluck('label')->map(fn (string $label): string => str_replace(':', '.', $label))->all(),
                    'datasets' => [[
                        'label' => 'Jumlah tiket',
                        'data' => $chartPoints->pluck('total')->all(),
                        'borderColor' => '#2878dc',
                        'backgroundColor' => 'rgba(40, 120, 220, 0.16)',
                        'pointBackgroundColor' => '#2878dc',
                        'pointBorderColor' => '#ffffff',
                        'pointBorderWidth' => 1.5,
                        'borderWidth' => 2,
                        'fill' => true,
                        'tension' => 0.15,
                        'pointRadius' => 3,
                        'pointHoverRadius' => 6,
                        'order' => 2,
                    ], ...($averageChartTotal > 0 ? [[
                        'label' => 'Rata-rata',
                        'data' => [
                            ['x' => str_replace(':', '.', (string) ($chartPoints->first()['label'] ?? '07:30')), 'y' => $averageChartTotal],
                            ['x' => str_replace(':', '.', (string) ($chartPoints->last()['label'] ?? '15:00')), 'y' => $averageChartTotal],
                        ],
                        'borderColor' => '#9ca3af',
                        'backgroundColor' => '#9ca3af',
                        'borderWidth' => 1.5,
                        'borderDash' => [4, 5],
                        'pointRadius' => 0,
                        'pointHoverRadius' => 0,
                        'fill' => false,
                        'tension' => 0,
                        'order' => 1,
                        'averageMarker' => true,
                    ]] : []), ...($peakChartLabel && $queueAnalysis['peak_total'] > 0 ? [[
                        'label' => 'Jam puncak',
                        'data' => [
                            ['x' => $peakChartLabel, 'y' => 0],
                            ['x' => $peakChartLabel, 'y' => $chartMaximum],
                        ],
                        'borderColor' => '#f59e0b',
                        'backgroundColor' => '#f59e0b',
                        'borderWidth' => 2,
                        'borderDash' => [6, 6],
                        'pointRadius' => 0,
                        'pointHoverRadius' => 0,
                        'fill' => false,
                        'tension' => 0,
                        'order' => 1,
                    ]] : []), [
                        'label' => 'Penunjuk waktu',
                        'data' => [
                            ['x' => $cursorChartLabel, 'y' => 0],
                            ['x' => $cursorChartLabel, 'y' => $chartMaximum],
                        ],
                        'borderColor' => '#7c3aed',
                        'backgroundColor' => '#7c3aed',
                        'borderWidth' => 2,
                        'pointRadius' => 0,
                        'pointHoverRadius' => 0,
                        'fill' => false,
                        'tension' => 0,
                        'order' => 0,
                        'cursorMarker' => true,
                    ]],
                ];
                $chartOptions = [
                    'maintainAspectRatio' => false,
                    'responsive' => true,
                    'interaction' => [
                        'intersect' => false,
                        'mode' => 'index',
                    ],
                    'plugins' => [
                        'legend' => ['display' => false],
                        'tooltip' => ['enabled' => false],
                    ],
                    'scales' => [
                        'x' => [
                            'title' => ['display' => true, 'text' => 'Waktu pengambilan tiket (WIB)'],
                        ],
                        'y' => [
                            'min' => 0,
                            'max' => $chartMaximum,
                            'beginAtZero' => true,
                            'ticks' => ['precision' => 0, 'stepSize' => 100],
                            'title' => ['display' => true, 'text' => 'Jumlah tiket'],
                        ],
                    ],
                ];
                $chartKey = md5(json_encode([$analysisRange, $analysisZoneFilter, $chartData]));
            @endphp
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div><h2 class="text-lg font-semibold text-gray-950 dark:text-white">Analisis Antrean</h2><p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Pola pengambilan tiket dari pukul 07.30 hingga 15.00 WIB.</p></div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Periode<select wire:model.live="analysisRange" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"><option value="today">Hari ini</option><option value="7">7 hari terakhir</option><option value="30">30 hari terakhir</option></select></label>
                        <label class="text-sm font-medium text-gray-600 dark:text-gray-300">Zona<select wire:model.live="analysisZoneFilter" class="mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900"><option value="all">Semua zona</option>@foreach ($zoneOptions as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
                    </div>
                </div>
                <div class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-lg bg-primary-50 px-4 py-3 dark:bg-primary-950/30"><p class="text-xs font-medium text-primary-700 dark:text-primary-300">Jam puncak</p><p class="mt-1 text-lg font-bold text-primary-950 dark:text-primary-100">{{ $queueAnalysis['peak_label'] ? str_replace(':', '.', $queueAnalysis['peak_label']).' WIB' : '-' }}</p></div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800"><p class="text-xs font-medium text-gray-500 dark:text-gray-400">Tiket pada jam puncak</p><p class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ $queueAnalysis['peak_total'] }}</p></div>
                    <div class="rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-800"><p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total tiket · {{ $queueAnalysis['period_label'] }}</p><p class="mt-1 text-lg font-bold text-gray-950 dark:text-white">{{ $queueAnalysis['total'] }}</p></div>
                </div>
                <div class="mt-5" wire:key="queue-analysis-chart-{{ $chartKey }}">
                    <div
                        x-load
                        x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                        x-data="chart({
                            cachedData: @js($chartData),
                            options: @js($chartOptions),
                            type: 'line',
                        })"
                        x-init="
                            const canvas = $refs.canvas;
                            let isDraggingCursor = false;
                            let selectedCursorIndex = 1;
                            const peakLabel = @js($peakChartLabel);
                            const peakTotal = Number(@js($queueAnalysis['peak_total']));
                            const averageTotal = Number(@js($averageChartTotal));

                            const positionLabel = (element, x, y, placeBelow = false) => {
                                const activeChart = getChart();
                                if (! activeChart || ! element) return;

                                element.style.display = 'block';
                                const width = element.offsetWidth;
                                const height = element.offsetHeight;
                                const left = Math.max(
                                    activeChart.chartArea.left,
                                    Math.min(x - (width / 2), activeChart.chartArea.right - width),
                                );
                                let top = placeBelow ? y + 10 : y - height - 10;

                                if (top < activeChart.chartArea.top) top = y + 10;
                                if (top + height > activeChart.chartArea.bottom) top = y - height - 10;

                                element.style.left = `${left}px`;
                                element.style.top = `${top}px`;
                            };

                            const positionReferenceLabels = () => {
                                const activeChart = getChart();
                                if (! activeChart) return;

                                if (peakLabel && peakTotal > 0) {
                                    const peakIndex = activeChart.data.labels.indexOf(peakLabel);
                                    if (peakIndex >= 0 && selectedCursorIndex !== peakIndex) {
                                        $refs.peakInfo.textContent = `Jam puncak · ${peakLabel} WIB · ${peakTotal} tiket`;
                                        positionLabel(
                                            $refs.peakInfo,
                                            activeChart.scales.x.getPixelForValue(peakIndex),
                                            activeChart.scales.y.getPixelForValue(peakTotal),
                                        );
                                    } else {
                                        $refs.peakInfo.style.display = 'none';
                                    }
                                }

                                if (averageTotal > 0) {
                                    $refs.averageInfo.textContent = `Rata-rata ${averageTotal} tiket`;
                                    $refs.averageInfo.style.display = 'block';
                                    const averageWidth = $refs.averageInfo.offsetWidth;
                                    $refs.averageInfo.style.left = `${Math.max(activeChart.chartArea.left, activeChart.chartArea.right - averageWidth)}px`;
                                    $refs.averageInfo.style.top = `${Math.max(activeChart.chartArea.top, activeChart.scales.y.getPixelForValue(averageTotal) - $refs.averageInfo.offsetHeight - 4)}px`;
                                }
                            };

                            const selectCursorIndex = (selectedIndex) => {
                                const activeChart = getChart();
                                if (! activeChart) return;

                                const labels = activeChart.data.labels;
                                selectedCursorIndex = Math.max(0, Math.min(labels.length - 1, selectedIndex));
                                const selectedLabel = labels[selectedCursorIndex];
                                const selectedTotal = Number(activeChart.data.datasets[0].data[selectedCursorIndex] ?? 0);
                                const cursorDataset = activeChart.data.datasets.find((dataset) => dataset.cursorMarker === true);
                                const peakIndex = peakLabel ? labels.indexOf(peakLabel) : -1;
                                const overlapsPeak = selectedCursorIndex === peakIndex;

                                if (! cursorDataset) return;

                                cursorDataset.hidden = overlapsPeak;
                                cursorDataset.data = [
                                    { x: selectedLabel, y: 0 },
                                    { x: selectedLabel, y: activeChart.scales.y.max },
                                ];
                                $refs.cursorInfo.textContent = overlapsPeak
                                    ? `Jam puncak · ${selectedLabel} WIB · ${selectedTotal} tiket`
                                    : `${selectedLabel} WIB · ${selectedTotal} tiket`;
                                $refs.cursorInfo.style.color = overlapsPeak ? '#92400e' : '#6d28d9';
                                $refs.cursorInfo.style.borderColor = overlapsPeak ? '#f59e0b' : '#8b5cf6';
                                $refs.cursorInfo.style.backgroundColor = overlapsPeak ? '#fffbeb' : '#f5f3ff';

                                activeChart.update('none');
                                requestAnimationFrame(() => {
                                    const cursorX = activeChart.scales.x.getPixelForValue(selectedCursorIndex);
                                    const cursorY = activeChart.scales.y.getPixelForValue(selectedTotal);
                                    const isNearPeak = peakIndex >= 0 && Math.abs(selectedCursorIndex - peakIndex) <= 1;
                                    positionLabel($refs.cursorInfo, cursorX, cursorY, isNearPeak && ! overlapsPeak);
                                    positionReferenceLabels();
                                });
                            };

                            const moveCursor = (event) => {
                                const activeChart = getChart();

                                if (! activeChart) return;

                                const labels = activeChart.data.labels;
                                const canvasRect = canvas.getBoundingClientRect();
                                const pointerX = event.clientX - canvasRect.left;
                                const rawIndex = activeChart.scales.x.getValueForPixel(pointerX);
                                const selectedIndex = Math.max(0, Math.min(labels.length - 1, Math.round(rawIndex)));
                                selectCursorIndex(selectedIndex);
                            };

                            canvas.addEventListener('pointerdown', (event) => {
                                isDraggingCursor = true;
                                canvas.setPointerCapture(event.pointerId);
                                moveCursor(event);
                            });
                            canvas.addEventListener('pointermove', (event) => {
                                if (isDraggingCursor) moveCursor(event);
                            });
                            canvas.addEventListener('pointerup', (event) => {
                                isDraggingCursor = false;
                                canvas.releasePointerCapture(event.pointerId);
                            });
                            canvas.addEventListener('pointercancel', () => isDraggingCursor = false);
                            const initializeReferenceLabels = (attempt = 0) => {
                                const activeChart = getChart();
                                if (! activeChart || ! activeChart.chartArea) {
                                    if (attempt < 20) requestAnimationFrame(() => initializeReferenceLabels(attempt + 1));
                                    return;
                                }

                                selectCursorIndex(Math.min(1, activeChart.data.labels.length - 1));
                            };
                            requestAnimationFrame(() => initializeReferenceLabels());
                            const chartResizeObserver = new ResizeObserver(() => {
                                requestAnimationFrame(() => selectCursorIndex(selectedCursorIndex));
                            });
                            chartResizeObserver.observe($refs.chartWrapper);
                        "
                        wire:ignore
                        class="fi-color-primary"
                    >
                        <div x-ref="chartWrapper" style="position: relative; height: 320px;">
                            <canvas x-ref="canvas" style="touch-action: pan-y;" role="img" aria-label="Grafik jumlah tiket per interval tiga puluh menit dengan penunjuk waktu yang dapat digeser"></canvas>
                            <span x-ref="cursorInfo" style="display: none; position: absolute; z-index: 3; pointer-events: none; white-space: nowrap; border: 1px solid #8b5cf6; border-radius: 0.5rem; padding: 0.25rem 0.55rem; background: #f5f3ff; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12); font-size: 0.75rem; line-height: 1.25rem; font-weight: 700; color: #6d28d9;"></span>
                            <span x-ref="peakInfo" style="display: none; position: absolute; z-index: 2; pointer-events: none; white-space: nowrap; border: 1px solid #f59e0b; border-radius: 0.5rem; padding: 0.25rem 0.55rem; background: #fffbeb; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.1); font-size: 0.75rem; line-height: 1.25rem; font-weight: 700; color: #92400e;"></span>
                            <span x-ref="averageInfo" style="display: none; position: absolute; z-index: 1; pointer-events: none; white-space: nowrap; padding: 0.1rem 0.35rem; background: rgba(255, 255, 255, 0.9); font-size: 0.68rem; line-height: 1rem; font-weight: 600; color: #6b7280;"></span>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.35rem 1rem; margin-top: 0.5rem; font-size: 0.75rem; line-height: 1.25rem; color: #6b7280;">
                            <span><span style="display: inline-block; width: 1rem; height: 2px; margin-right: 0.25rem; vertical-align: middle; background: #f59e0b;"></span>Oranye: jam puncak otomatis</span>
                            <span><span style="display: inline-block; width: 1rem; height: 2px; margin-right: 0.25rem; vertical-align: middle; background: #7c3aed;"></span>Ungu: geser untuk melihat waktu</span>
                            <span><span style="display: inline-block; width: 1rem; height: 0; margin-right: 0.25rem; vertical-align: middle; border-top: 1px dashed #9ca3af;"></span>Abu-abu: rata-rata per interval</span>
                        </div>
                        <span x-ref="backgroundColorElement" class="text-primary-50 dark:text-primary-400/10"></span>
                        <span x-ref="borderColorElement" class="text-primary-600 dark:text-primary-400"></span>
                        <span x-ref="gridColorElement" class="text-gray-200 dark:text-gray-800"></span>
                        <span x-ref="textColorElement" class="text-gray-500 dark:text-gray-400"></span>
                    </div>
                </div>
                <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Hanya menghitung tiket yang berhasil diterbitkan; tiket batal dan proses cetak yang belum selesai tidak termasuk.</p>
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
