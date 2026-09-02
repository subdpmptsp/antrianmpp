<x-filament-panels::page>
    @php
        $instansiOptions = $this->getInstansiOptions();
        $statusLabels = ['present' => 'Hadir', 'absent' => 'Belum hadir', 'unassigned' => 'Instansi belum diatur'];
    @endphp

    <style>
        .attendance-shell { --attendance-border: #e5e7eb; --attendance-muted: #64748b; }
        .attendance-tabs { display:flex; gap:.5rem; padding:.35rem; border:1px solid var(--attendance-border); border-radius:.9rem; background:#f8fafc; width:fit-content; }
        .attendance-tab { border:0; border-radius:.65rem; padding:.65rem 1rem; color:#475569; font-weight:700; cursor:pointer; background:transparent; }
        .attendance-tab.is-active { color:#fff; background:#2563eb; box-shadow:0 4px 12px rgba(37,99,235,.2); }
        .attendance-summary { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:2.5rem; padding:.25rem .35rem; }
        .attendance-zones { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:.75rem; }
        .attendance-zone { min-height:5.35rem; padding:.85rem; border:1px solid var(--attendance-border); border-radius:.9rem; background:#fff; text-align:left; transition:.16s ease; cursor:pointer; }
        .attendance-zone:hover { transform:translateY(-1px); box-shadow:0 6px 16px rgba(15,23,42,.08); }
        .attendance-zone.is-selected { border:3px solid #2563eb; padding:calc(.85rem - 2px); box-shadow:0 0 0 3px rgba(37,99,235,.12); }
        .attendance-zone.is-complete { border-color:#16a34a; background:#16a34a; color:#fff; }
        .attendance-zone.is-incomplete { border-color:#fda4af; background:#fff1f2; color:#9f1239; }
        .attendance-zone__name { display:block; font-size:.76rem; font-weight:800; }
        .attendance-zone__value { display:block; margin-top:.22rem; font-size:1.4rem; font-weight:900; line-height:1; }
        .attendance-zone__meta { display:block; margin-top:.32rem; font-size:.72rem; opacity:.82; }
        .attendance-stat { min-width:0; }
        .attendance-stat__label { color:#475569; font-size:.8rem; font-weight:700; }
        .attendance-stat__value { margin-top:.35rem; color:#0f172a; font-size:1.7rem; line-height:1; font-weight:850; }
        .attendance-stat__detail { color:#64748b; font-size:.85rem; font-weight:600; }
        .attendance-progress { height:.36rem; margin-top:.8rem; overflow:hidden; border-radius:999px; background:#f1f5f9; }
        .attendance-progress > span { display:block; height:100%; border-radius:inherit; }
        .attendance-progress--success > span { background:#16a34a; }
        .attendance-progress--danger > span { background:#dc2626; }
        .attendance-panel { border:1px solid var(--attendance-border); border-radius:1rem; background:#fff; overflow:hidden; }
        .attendance-panel__head { display:flex; align-items:center; justify-content:space-between; gap:1rem; padding:1rem 1.2rem; border-bottom:1px solid var(--attendance-border); }
        .attendance-panel__title { margin:0; font-size:1rem; font-weight:800; color:#0f172a; }
        .attendance-count { min-width:2rem; padding:.2rem .55rem; text-align:center; border-radius:999px; font-size:.75rem; font-weight:800; }
        .attendance-count--danger { color:#991b1b; background:#fee2e2; }
        .attendance-count--success { color:#166534; background:#dcfce7; }
        .attendance-list { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); }
        .attendance-person { display:flex; align-items:center; gap:.8rem; min-width:0; padding:.9rem 1.2rem; border-bottom:1px solid #f1f5f9; }
        .attendance-person:nth-child(odd) { border-right:1px solid #f1f5f9; }
        .attendance-dot { width:.65rem; height:.65rem; border-radius:999px; flex:0 0 auto; }
        .attendance-dot--danger { background:#ef4444; box-shadow:0 0 0 4px #fee2e2; }
        .attendance-dot--success { background:#22c55e; box-shadow:0 0 0 4px #dcfce7; }
        .attendance-person__body { min-width:0; flex:1; }
        .attendance-person__name { color:#0f172a; font-weight:750; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .attendance-person__institution { color:var(--attendance-muted); font-size:.8rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .attendance-person__time { color:#166534; font-size:.82rem; font-weight:800; white-space:nowrap; }
        .attendance-controls { display:grid; grid-template-columns:repeat(6,minmax(0,1fr)); gap:.8rem; align-items:end; }
        .attendance-field label { display:block; margin-bottom:.35rem; color:#475569; font-size:.78rem; font-weight:700; }
        .attendance-field input,.attendance-field select { width:100%; min-height:2.55rem; border:1px solid #cbd5e1; border-radius:.65rem; padding:.55rem .7rem; color:#0f172a; background:#fff; }
        .attendance-table-wrap { overflow-x:auto; }
        .attendance-table { width:100%; border-collapse:collapse; }
        .attendance-table th { padding:.75rem 1rem; text-align:left; color:#475569; background:#f8fafc; font-size:.72rem; text-transform:uppercase; letter-spacing:.04em; white-space:nowrap; }
        .attendance-table td { padding:.8rem 1rem; border-top:1px solid #f1f5f9; color:#334155; font-size:.86rem; }
        .attendance-badge { display:inline-flex; align-items:center; border-radius:999px; padding:.25rem .6rem; font-size:.75rem; font-weight:800; white-space:nowrap; }
        .attendance-badge--present { color:#166534; background:#dcfce7; }
        .attendance-badge--absent { color:#991b1b; background:#fee2e2; }
        .attendance-badge--unassigned { color:#9a3412; background:#ffedd5; }
        .attendance-empty { padding:2rem 1rem !important; text-align:center !important; color:var(--attendance-muted) !important; }
        .attendance-recap-cell { min-width:4.3rem; text-align:center !important; font-weight:800; }
        .attendance-recap-cell.is-good { color:#166534; background:#f0fdf4; }
        .attendance-recap-cell.is-warning { color:#9a3412; background:#fff7ed; }
        .attendance-recap-cell.is-danger { color:#991b1b; background:#fef2f2; }
        .attendance-recap-cell.is-future { color:#94a3b8; background:#f8fafc; }
        @media(max-width:1024px){.attendance-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.attendance-zones{grid-template-columns:repeat(3,minmax(0,1fr))}.attendance-controls{grid-template-columns:repeat(2,minmax(0,1fr)}}
        @media(max-width:640px){.attendance-tabs{width:100%;overflow-x:auto}.attendance-tab{flex:1 0 auto}.attendance-summary,.attendance-zones,.attendance-list,.attendance-controls{grid-template-columns:1fr}.attendance-person:nth-child(odd){border-right:0}}
        .dark .attendance-shell { --attendance-border:#334155; --attendance-muted:#94a3b8; }
        .dark .attendance-tabs,.dark .attendance-table th{background:#0f172a}.dark .attendance-stat,.dark .attendance-panel,.dark .attendance-field input,.dark .attendance-field select{background:#111827}.dark .attendance-stat__value,.dark .attendance-panel__title,.dark .attendance-person__name,.dark .attendance-field input,.dark .attendance-field select{color:#f8fafc}.dark .attendance-person,.dark .attendance-table td{border-color:#1e293b}
    </style>

    <div class="attendance-shell space-y-5">
        <div class="attendance-tabs" role="tablist" aria-label="Bagian laporan kehadiran">
            <button type="button" wire:click="setSection('today')" class="attendance-tab {{ $activeSection === 'today' ? 'is-active' : '' }}">Kehadiran Hari Ini</button>
            <button type="button" wire:click="setSection('history')" class="attendance-tab {{ $activeSection === 'history' ? 'is-active' : '' }}">Riwayat</button>
            <button type="button" wire:click="setSection('monthly')" class="attendance-tab {{ $activeSection === 'monthly' ? 'is-active' : '' }}">Rekap Bulanan</button>
        </div>

        @if ($activeSection === 'today')
            @php($overview = $this->getTodayOverview())
            <div class="space-y-5" wire:poll.visible.300s="refreshDashboard">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div><h2 class="text-xl font-bold text-gray-950 dark:text-white">{{ \Carbon\Carbon::parse($currentDate)->translatedFormat('l, d F Y') }}</h2><p class="text-sm text-gray-500">Terakhir diperbarui {{ $lastUpdatedAt }} WIB · otomatis setiap 5 menit saat bagian ini aktif.</p></div>
                    <x-filament::button wire:click="refreshDashboard" wire:loading.attr="disabled" icon="heroicon-o-arrow-path" color="gray">Perbarui Data</x-filament::button>
                </div>

                <div>
                    <p class="mb-2 text-sm font-semibold text-gray-600">Filter berdasarkan zona</p>
                    <div class="attendance-zones">
                        <button type="button" wire:click="selectTodayZone('all')" class="attendance-zone {{ $todayZone === 'all' ? 'is-selected' : '' }}">
                            <span class="attendance-zone__name">Semua Zona</span>
                            <span class="attendance-zone__value">{{ $overview['absent_operators'] }}</span>
                            <span class="attendance-zone__meta">belum login · {{ $overview['attendance_percentage'] }}% hadir</span>
                        </button>
                        @foreach($overview['zones'] as $zone)
                            <button type="button" wire:click="selectTodayZone('{{ $zone['id'] }}')" class="attendance-zone {{ $todayZone === $zone['id'] ? 'is-selected' : '' }} {{ $zone['total_operators'] > 0 && $zone['attendance_percentage'] === 100 ? 'is-complete' : 'is-incomplete' }}">
                                <span class="attendance-zone__name">{{ $zone['name'] }}</span>
                                <span class="attendance-zone__value">{{ $zone['absent_operators'] }}</span>
                                <span class="attendance-zone__meta">belum login · {{ $zone['attendance_percentage'] }}% hadir</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="attendance-summary">
                    @php($totalInstansi = $overview['represented_instansis'] + $overview['unrepresented_instansis'])
                    @php($instansiPercentage = $totalInstansi > 0 ? (int) round(($overview['represented_instansis'] / $totalInstansi) * 100) : 0)
                    @php($absentPercentage = $overview['total_operators'] > 0 ? (int) round(($overview['absent_operators'] / $overview['total_operators']) * 100) : 0)
                    <div class="attendance-stat"><div class="attendance-stat__label">Instansi terwakili</div><div class="attendance-stat__value">{{ $overview['represented_instansis'] }} <span class="attendance-stat__detail">dari {{ $totalInstansi }} instansi</span></div><div class="attendance-progress attendance-progress--success"><span style="width: {{ $instansiPercentage }}%"></span></div></div>
                    <div class="attendance-stat"><div class="attendance-stat__label">Petugas belum login, {{ strtolower($overview['zone_label']) }}</div><div class="attendance-stat__value text-rose-600">{{ $overview['absent_operators'] }} <span class="attendance-stat__detail text-rose-600">dari {{ $overview['total_operators'] }} petugas ({{ $absentPercentage }}%)</span></div><div class="attendance-progress attendance-progress--danger"><span style="width: {{ $absentPercentage }}%"></span></div></div>
                </div>

                <div class="attendance-controls" style="grid-template-columns:minmax(0,2fr) minmax(0,1fr)">
                    <div class="attendance-field"><label for="today-search">Cari petugas atau instansi</label><input id="today-search" type="search" wire:model.live.debounce.400ms="todaySearch" placeholder="Ketik nama petugas/instansi..."></div>
                    <div class="attendance-field"><label for="today-instansi">Instansi</label><select id="today-instansi" wire:model.live="todayInstansi"><option value="">Semua instansi</option>@foreach($instansiOptions as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></div>
                </div>

                @if($overview['unassigned']->isNotEmpty())<div class="rounded-xl border border-orange-200 bg-orange-50 p-4 text-sm text-orange-900"><strong>Perlu melengkapi master data:</strong> {{ $overview['unassigned']->pluck('name')->join(', ') }} belum terhubung ke instansi sehingga belum masuk perhitungan.</div>@endif

                <section class="attendance-panel">
                    <div class="attendance-panel__head"><h3 class="attendance-panel__title">Perlu Perhatian — Belum Hadir</h3><span class="attendance-count attendance-count--danger">{{ $overview['absent']->count() }}</span></div>
                    @if($overview['absent']->isEmpty())<div class="attendance-empty">Tidak ada petugas yang belum hadir pada filter ini.</div>@else<div class="attendance-list">@foreach($overview['absent'] as $row)<div class="attendance-person"><span class="attendance-dot attendance-dot--danger"></span><div class="attendance-person__body"><div class="attendance-person__name">{{ $row['name'] }}</div><div class="attendance-person__institution">{{ $row['instansi'] }}</div></div><span class="attendance-badge attendance-badge--absent">Belum login</span></div>@endforeach</div>@endif
                </section>

                <section class="attendance-panel">
                    <div class="attendance-panel__head"><h3 class="attendance-panel__title">Sudah Hadir</h3><span class="attendance-count attendance-count--success">{{ $overview['present']->count() }}</span></div>
                    @if($overview['present']->isEmpty())<div class="attendance-empty">Belum ada petugas yang login pada filter ini.</div>@else<div class="attendance-list">@foreach($overview['present'] as $row)<div class="attendance-person"><span class="attendance-dot attendance-dot--success"></span><div class="attendance-person__body"><div class="attendance-person__name">{{ $row['name'] }}</div><div class="attendance-person__institution">{{ $row['instansi'] }}</div></div><span class="attendance-person__time">{{ $row['check_in'] }} WIB</span></div>@endforeach</div>@endif
                </section>

                @if($overview['off']->isNotEmpty())<details class="attendance-panel"><summary class="attendance-panel__head" style="cursor:pointer"><span class="attendance-panel__title">Petugas Tidak Terjadwal / Hari Libur</span><span class="attendance-count">{{ $overview['off']->count() }}</span></summary><div class="attendance-list">@foreach($overview['off'] as $row)<div class="attendance-person"><div class="attendance-person__body"><div class="attendance-person__name">{{ $row['name'] }}</div><div class="attendance-person__institution">{{ $row['instansi'] }}</div></div><span class="attendance-badge">Libur</span></div>@endforeach</div></details>@endif
            </div>
        @elseif ($activeSection === 'history')
            @include('filament.resources.attendance-resource.pages.partials.history', compact('instansiOptions', 'statusLabels'))
        @else
            @include('filament.resources.attendance-resource.pages.partials.monthly')
        @endif
    </div>
</x-filament-panels::page>
