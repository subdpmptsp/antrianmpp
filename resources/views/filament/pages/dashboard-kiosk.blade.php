@if (! $selectedZoneIsValid)
    <main class="tv-zone-selector">
        <a href="{{ route('filament.admin.pages.monitoring-dashboard') }}" class="tv-zone-selector__admin-link">← Kembali ke Panel Admin</a>
        <section class="tv-zone-selector__hero">
            <img src="{{ $mppBranding['logo_url'] }}" alt="Logo {{ $mppBranding['name'] }}">
            <div>
                <p>SIOLA MALL PELAYANAN PUBLIK</p>
                <h1>Pilih Zona TV Ruang Tunggu</h1>
                <span>Pilih zona sebelum layar penampil antrean dijalankan.</span>
            </div>
        </section>

        <section class="tv-zone-selector__grid" aria-label="Pilihan zona TV">
            @foreach ($zones as $zone)
                <a href="{{ route('filament.admin.pages.dashboard-kiosk', ['zone' => $zone['name']]) }}" class="tv-zone-card">
                    <strong>{{ $zone['number'] }}</strong>
                    <span>{{ $zone['name'] }}</span>
                    <small>Buka tampilan TV</small>
                </a>
            @endforeach
        </section>
    </main>
@else
    @php
        $calledCounters = $counters
            ->filter(fn ($counter) => $counter->activeQueue)
            ->sortByDesc(fn ($counter) => $counter->activeQueue?->called_at?->getTimestamp() ?? 0)
            ->values();
        $primaryCounter = $calledCounters->first() ?? $counters->first();
        $primaryQueue = $primaryCounter?->activeQueue;
        $nextQueue = $primaryCounter?->nextQueue;
        $totalInZone = $counters->sum('today_queue_count');
        $waitingInZone = $counters->sum('waiting_queue_count');
        $calledAt = $primaryQueue?->called_at?->toIso8601String();
    @endphp

    <main class="tv-display" wire:poll.3s="refreshData" data-tv-display data-scroll-speed="24">
        <header class="tv-display__header">
            <div class="tv-display__brand">
                <img src="{{ $mppBranding['logo_url'] }}" alt="Logo {{ $mppBranding['name'] }}">
                <div>
                    <strong>{{ $mppBranding['name'] }}</strong>
                    <span>TV Ruang Tunggu · {{ $selectedZone }}</span>
                </div>
            </div>

            <div class="tv-display__controls">
                <label class="tv-display__speed-control">
                    <span>Kecepatan gulir</span>
                    <input type="range" min="8" max="60" step="2" value="24" data-tv-speed-control aria-label="Kecepatan gulir antrean">
                </label>
                <div class="tv-display__datetime">
                    <strong data-tv-clock>--:--:--</strong>
                    <span data-tv-date>Memuat tanggal...</span>
                </div>
                <button type="button" class="tv-display__fullscreen" data-tv-fullscreen aria-label="Layar penuh">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3m13-5h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3m13 5h3a2 2 0 0 0 2-2v-3"/></svg>
                </button>
                <a href="{{ route('filament.admin.pages.monitoring-dashboard') }}" class="tv-display__back">Panel Admin</a>
                <a href="{{ route('filament.admin.pages.dashboard-kiosk') }}" class="tv-display__back">Ganti zona</a>
            </div>
        </header>

        <section class="tv-display__layout">
            <article class="tv-current-card">
                <div class="tv-current-card__label">Sedang dipanggil</div>
                <div class="tv-current-card__number {{ $primaryQueue ? '' : 'is-empty' }}">
                    {{ $primaryQueue?->number ?? '—' }}
                </div>
                <div class="tv-current-card__service">
                    <strong>{{ $primaryCounter?->display_name ?? 'Belum ada loket aktif' }}</strong>
                    <span>{{ $primaryQueue?->service?->name ?? 'Menunggu panggilan antrean' }}</span>
                </div>

                <div class="tv-current-card__duration">
                    <span>Waktu layanan</span>
                    <strong data-service-duration data-called-at="{{ $calledAt }}">00:00:00</strong>
                </div>

                <div class="tv-current-card__summary">
                    <div>
                        <span>Total antrean zona ini</span>
                        <strong>{{ $totalInZone }}</strong>
                    </div>
                    <div class="is-next">
                        <span>Nomor antrean berikutnya</span>
                        <strong>{{ $nextQueue?->number ?? '—' }}</strong>
                    </div>
                </div>
            </article>

            <aside class="tv-queue-card">
                <header class="tv-queue-card__header">
                    <div>
                        <strong>{{ $waitingInZone }} orang dalam antrean</strong>
                        <span>{{ $selectedZone }} · Daftar antrean berjalan</span>
                    </div>
                    <em>Antrean berjalan</em>
                </header>

                <div class="tv-queue-card__scroll" data-tv-scroll-list>
                    <div class="tv-queue-card__items">
                        @forelse ($counters as $counter)
                            @php
                                $displayQueue = $counter->activeQueue ?? $counter->nextQueue;
                                $isCalled = (bool) $counter->activeQueue;
                                $status = ! $counter->is_active ? 'Tutup' : ($isCalled ? 'Dipanggil' : ($displayQueue ? 'Menunggu' : 'Tidak ada antrean'));
                            @endphp
                            <article class="tv-queue-row {{ $isCalled ? 'is-called' : '' }} {{ ! $counter->is_active ? 'is-closed' : '' }}">
                                <div class="tv-queue-row__number">{{ $displayQueue?->number ?? '—' }}</div>
                                <div class="tv-queue-row__service">
                                    <strong>{{ $counter->service?->name ?? 'Layanan belum diatur' }}</strong>
                                    <span>{{ $counter->display_name }}</span>
                                </div>
                                <span class="tv-queue-row__status">{{ $status }}</span>
                            </article>
                        @empty
                            <div class="tv-queue-card__empty">Belum ada loket pada {{ $selectedZone }}.</div>
                        @endforelse
                    </div>
                </div>
            </aside>
        </section>

        <footer class="tv-display__footer">
            <span>{{ $selectedZone }} · Sistem antrean terhubung</span>
            <span>{{ $counters->where('is_active', true)->count() }} loket aktif · {{ $totalInZone }} nomor hari ini</span>
        </footer>
    </main>
@endif

@push('styles')
    <style>
        :root { color-scheme: light; }
        .tv-zone-selector, .tv-display { min-height: 100vh; box-sizing: border-box; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        .tv-zone-selector { padding: clamp(2rem, 6vw, 6rem); color: #fff; background: radial-gradient(circle at 12% 15%, #4e95ff 0, transparent 32%), linear-gradient(135deg, #0b2f70, #1769de); }
        .tv-zone-selector__admin-link { display:block; width:fit-content; margin:0 auto 1.5rem; color:#fff; font-size:.9rem; font-weight:700; text-decoration:none; opacity:.9; }
        .tv-zone-selector__admin-link:hover { text-decoration:underline; opacity:1; }
        .tv-zone-selector__hero { display: flex; align-items: center; gap: 1.5rem; max-width: 1100px; margin: 0 auto 3rem; }
        .tv-zone-selector__hero img { width: 74px; height: 74px; object-fit: contain; background: #fff; border-radius: 18px; padding: .55rem; }
        .tv-zone-selector__hero p { margin: 0 0 .35rem; font-size: .78rem; font-weight: 800; letter-spacing: .14em; opacity: .78; }
        .tv-zone-selector__hero h1 { margin: 0; font-size: clamp(2rem, 5vw, 4rem); line-height: 1.05; }
        .tv-zone-selector__hero span { display: block; margin-top: .7rem; font-size: clamp(1rem, 2vw, 1.35rem); opacity: .82; }
        .tv-zone-selector__grid { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 1.25rem; max-width: 1300px; margin: auto; }
        .tv-zone-card { min-height: 240px; display: block; box-sizing: border-box; padding: 3.2rem 1rem 1rem; border: 0; border-radius: 24px; cursor: pointer; color: #102548; text-align: center; text-decoration: none; background: #fff; box-shadow: 0 18px 42px rgba(4, 25, 67, .25); transition: transform .2s ease, box-shadow .2s ease; }
        .tv-zone-card:hover { transform: translateY(-6px); box-shadow: 0 28px 52px rgba(4, 25, 67, .35); }
        .tv-zone-card strong, .tv-zone-card span, .tv-zone-card small { display: block; }
        .tv-zone-card strong { font-size: 5rem; line-height: 1; color: #2671df; }
        .tv-zone-card span { margin-top: 1rem; font-size: 1.25rem; font-weight: 850; }
        .tv-zone-card small { margin-top: .6rem; color: #73829b; }
        .tv-display { padding: clamp(.75rem, 1.7vw, 1.7rem); color: #102548; background: #edf4fd; }
        .tv-display__header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .85rem 1.1rem; border-radius: 16px 16px 0 0; color: #fff; background: linear-gradient(100deg, #1a66d5, #2e86f2); }
        .tv-display__brand, .tv-display__controls { display: flex; align-items: center; gap: .8rem; }
        .tv-display__brand img { width: 42px; height: 42px; object-fit: contain; padding: .25rem; border-radius: 10px; background: #fff; }
        .tv-display__brand strong, .tv-display__brand span { display: block; }
        .tv-display__brand strong { font-size: clamp(.95rem, 1.6vw, 1.28rem); }
        .tv-display__brand span { margin-top: .15rem; font-size: .75rem; opacity: .85; }
        .tv-display__speed-control { display: grid; grid-template-columns: auto 105px; align-items: center; gap: .5rem; font-size: .72rem; font-weight: 700; }
        .tv-display__speed-control input { accent-color: #fff; }
        .tv-display__datetime { min-width: 150px; text-align: right; }
        .tv-display__datetime strong, .tv-display__datetime span { display: block; }
        .tv-display__datetime strong { font-size: clamp(1.25rem, 2.4vw, 2.1rem); line-height: 1; letter-spacing: .04em; }
        .tv-display__datetime span { margin-top: .3rem; font-size: clamp(.72rem, 1.1vw, .95rem); font-weight: 650; }
        .tv-display__fullscreen, .tv-display__back { border: 1px solid rgba(255,255,255,.35); color: #fff; background: rgba(8, 54, 127, .18); cursor: pointer; }
        .tv-display__fullscreen { width: 38px; height: 38px; display: grid; place-items: center; border-radius: 9px; }
        .tv-display__fullscreen svg { width: 20px; fill: none; stroke: currentColor; stroke-width: 2; }
        .tv-display__back { padding: .55rem .75rem; border-radius: 8px; font-weight: 700; text-decoration: none; }
        .tv-display__layout { display: grid; grid-template-columns: minmax(0, 3fr) minmax(340px, 2fr); gap: clamp(1rem, 1.7vw, 1.7rem); padding: clamp(1rem, 2vw, 1.8rem); border: 1px solid #d4e1f0; border-top: 0; background: rgba(255,255,255,.72); }
        .tv-current-card, .tv-queue-card { border: 1px solid #d7e1ee; border-radius: 18px; overflow: hidden; background: #fff; box-shadow: 0 12px 28px rgba(31, 73, 125, .08); }
        .tv-current-card { height: auto; min-height: min(68vh, 680px); }
        .tv-queue-card { height: min(68vh, 680px); min-height: 0; }
        .tv-current-card { display: flex; flex-direction: column; align-items: center; padding: clamp(1.5rem, 3.5vw, 3.7rem); text-align: center; }
        .tv-current-card__label { align-self: stretch; padding: .65rem 1rem; border-radius: 11px; color: #fff; background: linear-gradient(90deg, #1769d5, #3a8df4); box-shadow: 0 6px 14px rgba(33, 104, 207, .18); font-size: clamp(.9rem, 1.5vw, 1.2rem); font-weight: 850; letter-spacing: .08em; text-transform: uppercase; }
        .tv-current-card__number { box-sizing: border-box; flex: 0 0 auto; display: grid; place-items: center; width: min(100%, 610px); min-height: clamp(132px, 19vh, 220px); margin: clamp(1rem, 2vw, 1.8rem) auto; padding: .25rem 1rem; border: 2px solid #4a92ee; border-radius: 18px; color: #104f9f; background: #cfe3fd; font-size: clamp(3.5rem, 8vw, 8rem); font-weight: 900; line-height: .92; letter-spacing: .04em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tv-current-card__number.is-empty { color: #8a9bb3; background: #f1f5fa; border-color: #d9e1eb; }
        .tv-current-card__service strong, .tv-current-card__service span { display: block; }
        .tv-current-card__service strong { font-size: clamp(1.15rem, 2.1vw, 1.75rem); }
        .tv-current-card__service span { margin-top: .4rem; color: #6380a8; font-size: clamp(.9rem, 1.5vw, 1.2rem); }
        .tv-current-card__duration { margin: clamp(1.2rem, 2vw, 2rem) 0; }
        .tv-current-card__duration span, .tv-current-card__duration strong { display: block; }
        .tv-current-card__duration span { color: #6a7f9f; font-size: .95rem; }
        .tv-current-card__duration strong { margin-top: .35rem; color: #1555ad; font-size: clamp(1.7rem, 3vw, 2.8rem); }
        .tv-current-card__summary { width: 100%; margin: .9rem 0 0; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .tv-current-card__summary div { padding: 1rem; border-radius: 13px; background: #f8fbff; }
        .tv-current-card__summary .is-next { color: #175f32; background: #cceecb; }
        .tv-current-card__summary span, .tv-current-card__summary strong { display: block; }
        .tv-current-card__summary span { font-size: clamp(.72rem, 1.15vw, .96rem); }
        .tv-current-card__summary strong { margin-top: .4rem; font-size: clamp(1.6rem, 3vw, 2.8rem); }
        .tv-queue-card { display: flex; flex-direction: column; }
        .tv-queue-card__header { display: flex; justify-content: space-between; gap: 1rem; align-items: center; padding: 1rem 1.15rem; border-bottom: 1px solid #dce6f1; }
        .tv-queue-card__header strong, .tv-queue-card__header span { display: block; }
        .tv-queue-card__header strong { font-size: clamp(1rem, 1.6vw, 1.35rem); }
        .tv-queue-card__header span { margin-top: .25rem; color: #6881a4; font-size: .8rem; }
        .tv-queue-card__header em { padding: .42rem .7rem; border-radius: 999px; color: #168548; background: #d9f2d7; font-size: .75rem; font-style: normal; font-weight: 800; white-space: nowrap; }
        .tv-queue-card__scroll { position: relative; flex: 1; min-height: 0; overflow: hidden; padding: .85rem; }
        .tv-queue-card__scroll::before, .tv-queue-card__scroll::after { content: ''; position: absolute; z-index: 1; right: 0; left: 0; height: 18px; pointer-events: none; }
        .tv-queue-card__scroll::before { top: 0; background: linear-gradient(#fff, transparent); }
        .tv-queue-card__scroll::after { bottom: 0; background: linear-gradient(transparent, #fff); }
        .tv-queue-card__items { display: grid; gap: .7rem; }
        .tv-queue-row { display: grid; grid-template-columns: minmax(68px, .75fr) minmax(0, 1.6fr) auto; align-items: center; gap: .75rem; min-height: 68px; padding: .65rem .75rem; border: 1px solid #dce4ef; border-radius: 12px; background: #fff; }
        .tv-queue-row.is-called { border-color: #5599ed; background: #cfe4fd; }
        .tv-queue-row.is-closed { opacity: .58; }
        .tv-queue-row__number { color: #123d77; font-size: clamp(1rem, 2vw, 1.6rem); font-weight: 900; white-space: nowrap; }
        .tv-queue-row__service { min-width: 0; }
        .tv-queue-row__service strong, .tv-queue-row__service span { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .tv-queue-row__service strong { font-size: clamp(.83rem, 1.25vw, 1.05rem); }
        .tv-queue-row__service span { margin-top: .18rem; color: #6e82a0; font-size: .74rem; }
        .tv-queue-row__status { padding: .38rem .6rem; border-radius: 999px; color: #6b7d95; background: #f1f4f8; font-size: .7rem; font-weight: 800; white-space: nowrap; }
        .tv-queue-row.is-called .tv-queue-row__status { color: #fff; background: #317ce5; }
        .tv-queue-card__empty { display: grid; place-items: center; min-height: 180px; color: #718197; text-align: center; }
        .tv-display__footer { display: flex; justify-content: space-between; gap: 1rem; padding: .7rem 1.1rem; color: #5b7294; border: 1px solid #d4e1f0; border-top: 0; border-radius: 0 0 16px 16px; background: #fff; font-size: .78rem; font-weight: 650; }
        @media (max-width: 900px) { .tv-zone-selector__grid { grid-template-columns: repeat(2, 1fr); } .tv-display__layout { grid-template-columns: 1fr; } .tv-current-card, .tv-queue-card { height: auto; min-height: auto; } .tv-queue-card__scroll { max-height: 360px; } .tv-display__speed-control { display: none; } }
        @media (max-width: 620px) { .tv-zone-selector__grid { grid-template-columns: 1fr; } .tv-display__header, .tv-display__controls { align-items: flex-start; flex-wrap: wrap; } .tv-display__datetime { text-align: left; } .tv-display__back { display: none; } .tv-current-card__summary { grid-template-columns: 1fr; } .tv-display__footer { display: block; } .tv-display__footer span + span { display: block; margin-top: .35rem; } }
    </style>
@endpush

@push('scripts')
    <script>
        (() => {
            let previousTime = performance.now()
            let scrollTop = 0
            let pauseUntil = 0

            const updateClock = () => {
                const now = new Date()
                document.querySelectorAll('[data-tv-clock]').forEach((element) => {
                    element.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replaceAll('.', ':')
                })
                document.querySelectorAll('[data-tv-date]').forEach((element) => {
                    element.textContent = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
                })
                document.querySelectorAll('[data-service-duration]').forEach((element) => {
                    const calledAt = element.dataset.calledAt
                    if (!calledAt) return
                    const seconds = Math.max(0, Math.floor((now.getTime() - new Date(calledAt).getTime()) / 1000))
                    const hours = String(Math.floor(seconds / 3600)).padStart(2, '0')
                    const minutes = String(Math.floor((seconds % 3600) / 60)).padStart(2, '0')
                    const remainingSeconds = String(seconds % 60).padStart(2, '0')
                    element.textContent = `${hours}:${minutes}:${remainingSeconds}`
                })
            }

            const scrollQueueList = (time) => {
                const list = document.querySelector('[data-tv-scroll-list]')
                const root = document.querySelector('[data-tv-display]')
                const elapsed = Math.min(80, time - previousTime)
                previousTime = time

                if (list && root && list.scrollHeight > list.clientHeight) {
                    const maximum = list.scrollHeight - list.clientHeight
                    const speed = Number(root.dataset.scrollSpeed || 24)

                    if (time >= pauseUntil) {
                        scrollTop += (speed * elapsed) / 1000
                        if (scrollTop >= maximum) {
                            scrollTop = 0
                            pauseUntil = time + 1400
                        }
                        list.scrollTop = scrollTop
                    }
                } else {
                    scrollTop = 0
                }

                window.requestAnimationFrame(scrollQueueList)
            }

            document.addEventListener('click', async (event) => {
                if (!event.target.closest('[data-tv-fullscreen]')) return
                if (!document.fullscreenElement) await document.documentElement.requestFullscreen?.()
                else await document.exitFullscreen?.()
            })

            document.addEventListener('input', (event) => {
                const control = event.target.closest('[data-tv-speed-control]')
                const root = document.querySelector('[data-tv-display]')
                if (control && root) root.dataset.scrollSpeed = control.value
            })

            updateClock()
            window.setInterval(updateClock, 1000)
            window.requestAnimationFrame(scrollQueueList)
        })()
    </script>
@endpush
