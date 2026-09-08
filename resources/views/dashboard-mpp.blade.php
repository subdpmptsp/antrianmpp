<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light">
    <title>SIOLA Dalam Satu Data</title>
    @include('partials.siola-q-favicon')
    <style>
        :root {
            color-scheme: light;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #0b2547;
            background: #edf6ff;
        }

        * { box-sizing: border-box; }

        html, body { width: 100%; min-width: 960px; height: 100%; margin: 0; overflow: hidden; }

        body {
            background:
                radial-gradient(circle at 90% 10%, rgba(70, 151, 229, .16), transparent 28%),
                linear-gradient(135deg, #f9fcff 0%, #eef7ff 56%, #e5f1fc 100%);
        }

        .screen {
            width: 100vw;
            height: 100vh;
            padding: clamp(18px, 1.7vw, 34px);
            display: grid;
            grid-template-rows: clamp(90px, 10.5vh, 122px) minmax(0, 1fr);
            gap: clamp(14px, 1.25vw, 24px);
        }

        .topbar {
            display: grid;
            grid-template-columns: minmax(280px, 1fr) auto minmax(280px, 1fr);
            align-items: center;
            gap: 24px;
            padding: 0 4px clamp(12px, 1.1vh, 18px);
            border-bottom: 2px solid #c9def1;
        }

        .brand { display: flex; align-items: center; gap: 16px; min-width: 0; }
        .brand img { width: clamp(130px, 11vw, 210px); max-height: 78px; object-fit: contain; }
        .brand-copy { min-width: 0; }
        .brand-copy strong { display: block; font-size: clamp(17px, 1.25vw, 24px); color: #073b73; }
        .brand-copy span { display: block; margin-top: 4px; font-size: clamp(12px, .76vw, 15px); color: #647c96; white-space: nowrap; }

        .headline { text-align: center; white-space: nowrap; }
        .headline h1 { margin: 0; font-size: clamp(25px, 2.15vw, 42px); line-height: 1; letter-spacing: .045em; color: #073b73; }
        .headline p { margin: 9px 0 0; font-size: clamp(11px, .76vw, 15px); font-weight: 750; letter-spacing: .14em; color: #2876bd; text-transform: uppercase; }

        .clock { text-align: right; font-variant-numeric: tabular-nums; }
        .clock strong { display: block; font-size: clamp(24px, 2vw, 38px); line-height: 1; color: #073b73; }
        .clock span { display: block; margin-top: 8px; font-size: clamp(12px, .8vw, 16px); color: #647c96; }

        .dashboard-grid {
            min-height: 0;
            display: grid;
            grid-template-columns: 1.02fr 1fr 1fr;
            grid-template-rows: .82fr 1.18fr;
            gap: clamp(14px, 1.25vw, 24px);
        }

        .card {
            min-width: 0;
            min-height: 0;
            overflow: hidden;
            padding: clamp(17px, 1.45vw, 28px);
            border: 1px solid #d3e3f2;
            border-radius: clamp(16px, 1.2vw, 24px);
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 10px 26px rgba(20, 75, 127, .09);
        }

        .card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0;
            font-size: clamp(14px, 1.05vw, 21px);
            line-height: 1.2;
            color: #2b659d;
            letter-spacing: .045em;
            text-transform: uppercase;
        }

        .title-icon { width: clamp(25px, 1.7vw, 34px); height: clamp(25px, 1.7vw, 34px); display: grid; place-items: center; border-radius: 9px; color: #0867bb; background: #e5f2ff; }
        .title-icon svg { width: 65%; height: 65%; }

        .about { position: relative; border: 0; color: #fff; background: linear-gradient(145deg, #0758a5, #0a74d2); }
        .about::after { content: ""; position: absolute; right: -55px; bottom: -80px; width: 240px; height: 240px; border: 28px solid rgba(255,255,255,.08); border-radius: 50%; }
        .about .card-title { color: #c6e5ff; }
        .about .title-icon { color: #fff; background: rgba(255,255,255,.15); }
        .about h3 { position: relative; z-index: 1; max-width: 92%; margin: clamp(18px, 2vh, 30px) 0 0; font-size: clamp(24px, 2vw, 40px); line-height: 1.12; }
        .about p { position: relative; z-index: 1; max-width: 90%; margin: 15px 0 0; font-size: clamp(14px, 1.03vw, 20px); line-height: 1.45; color: #deefff; }

        .metrics { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: clamp(16px, 1.4vw, 28px); margin-top: clamp(24px, 3vh, 42px); }
        .metric strong { display: block; font-size: clamp(46px, 4.4vw, 86px); line-height: .9; font-weight: 850; color: #0758a5; font-variant-numeric: tabular-nums; }
        .metric.is-green strong { color: #138a55; }
        .metric span { display: block; margin-top: 13px; font-size: clamp(14px, 1vw, 20px); line-height: 1.25; font-weight: 700; color: #5e7690; }
        .live { display: inline-flex; align-items: center; gap: 9px; margin-top: clamp(17px, 2.2vh, 29px); font-size: clamp(12px, .78vw, 16px); color: #667f98; }
        .live::before { content: ""; width: 9px; height: 9px; border-radius: 50%; background: #1aa466; box-shadow: 0 0 0 6px rgba(26,164,102,.13); animation: pulse 2.4s ease-in-out infinite; }

        .scope { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: clamp(10px, .9vw, 17px); margin-top: clamp(26px, 4vh, 52px); }
        .scope-item { padding: clamp(18px, 2vw, 34px) 8px; text-align: center; border-radius: 16px; background: #edf6ff; }
        .scope-item strong { display: block; font-size: clamp(37px, 3.4vw, 66px); line-height: 1; color: #0758a5; font-variant-numeric: tabular-nums; }
        .scope-item span { display: block; margin-top: 10px; font-size: clamp(12px, .84vw, 17px); font-weight: 750; color: #5a738e; text-transform: uppercase; }

        .chart-shell { height: calc(100% - 44px); display: grid; grid-template-rows: minmax(0, 1fr) auto; gap: 7px; padding-top: 10px; }
        .chart { min-height: 0; display: flex; align-items: stretch; gap: clamp(8px, .7vw, 14px); padding: 16px 8px 0; border-bottom: 2px solid #dce8f3; background: repeating-linear-gradient(to bottom, transparent 0, transparent calc(33.333% - 1px), #e6eef6 33.333%); }
        .bar-slot { min-width: 0; flex: 1 1 0; display: flex; align-items: flex-end; justify-content: center; }
        .bar { width: min(64%, 38px); min-height: 4px; border-radius: 8px 8px 2px 2px; background: #b7d8f7; transition: height .6s ease; }
        .bar-slot:last-child .bar { background: linear-gradient(#1b85e5, #0867bb); }
        .chart-labels { display: flex; gap: clamp(8px, .7vw, 14px); padding: 0 8px; }
        .chart-labels span { min-width: 0; flex: 1 1 0; text-align: center; font-size: clamp(10px, .68vw, 14px); font-weight: 700; color: #667e96; }

        .services { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: clamp(8px, .72vw, 14px); margin-top: clamp(13px, 1.6vh, 22px); }
        .service { display: flex; align-items: center; gap: 12px; min-width: 0; padding: clamp(9px, .7vw, 14px); border-radius: 13px; background: #f1f7fd; }
        .service-icon { width: clamp(32px, 2.25vw, 44px); height: clamp(32px, 2.25vw, 44px); flex: 0 0 auto; display: grid; place-items: center; border-radius: 10px; color: #0758a5; background: #dcecff; }
        .service-icon svg { width: 56%; height: 56%; }
        .service span:last-child { overflow: hidden; font-size: clamp(12px, .84vw, 17px); font-weight: 750; color: #173b61; text-overflow: ellipsis; white-space: nowrap; }

        .institutions { display: grid; gap: clamp(6px, .7vh, 10px); margin-top: clamp(11px, 1.4vh, 19px); }
        .institution { display: flex; align-items: center; gap: 12px; min-width: 0; padding-bottom: clamp(6px, .7vh, 10px); border-bottom: 1px solid #e4edf6; }
        .institution:last-child { border-bottom: 0; }
        .institution b { width: clamp(28px, 1.8vw, 36px); height: clamp(28px, 1.8vw, 36px); flex: 0 0 auto; display: grid; place-items: center; border-radius: 50%; font-size: clamp(11px, .72vw, 14px); color: #0758a5; background: #e4f1fd; }
        .institution span { overflow: hidden; font-size: clamp(12px, .82vw, 17px); font-weight: 700; color: #173b61; text-overflow: ellipsis; white-space: nowrap; }
        .more { margin: 8px 0 0; text-align: right; font-size: clamp(11px, .7vw, 14px); font-weight: 750; color: #2876bd; }
        .empty { padding: 25px 0; color: #73879c; font-size: clamp(13px, .85vw, 17px); }

        @keyframes pulse { 50% { opacity: .4; transform: scale(.82); } }
        @media (prefers-reduced-motion: reduce) { *, *::before { animation: none !important; transition: none !important; } }
        @media (max-aspect-ratio: 4/3) { html, body { overflow: auto; } .screen { min-height: 720px; } }
    </style>
</head>
<body>
<main class="screen">
    <header class="topbar">
        <div class="brand">
            <img src="{{ asset('images/siola-queue-login-logo.png') }}" alt="Logo MPP SIOLA">
            <div class="brand-copy"><strong>MPP SIOLA</strong><span>Mal Pelayanan Publik Surabaya</span></div>
        </div>
        <div class="headline"><h1>SIOLA DALAM SATU DATA</h1><p>Digital Showcase MPP SIOLA</p></div>
        <div class="clock"><strong id="clock">--:-- WIB</strong><span id="date">--</span></div>
    </header>

    <section class="dashboard-grid" aria-label="Ringkasan data MPP SIOLA">
        <article class="card about">
            <h2 class="card-title"><span class="title-icon">{!! '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M6 18V9m4 9V9m4 9V9m4 9V9M4 6l8-3 8 3v3H4z"/></svg>' !!}</span>Tentang MPP SIOLA</h2>
            <h3>Satu tempat untuk berbagai kebutuhan pelayanan publik</h3>
            <p>Menghubungkan masyarakat dengan layanan pemerintah secara mudah, cepat, dan transparan.</p>
        </article>

        <article class="card">
            <h2 class="card-title"><span class="title-icon">{!! '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l2-7 4 14 2-7h6"/></svg>' !!}</span>Aktivitas hari ini</h2>
            <div class="metrics">
                <div class="metric"><strong id="tickets-today">0</strong><span>Tiket diterbitkan</span></div>
                <div class="metric is-green"><strong id="completed-today">0</strong><span>Pelayanan selesai</span></div>
            </div>
            <span class="live" id="updated-at">Data diperbarui otomatis</span>
        </article>

        <article class="card">
            <h2 class="card-title"><span class="title-icon">{!! '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="6" cy="6" r="2"/><circle cx="18" cy="6" r="2"/><circle cx="12" cy="18" r="2"/><path d="m8 7 3 9m5-9-3 9M8 6h8"/></svg>' !!}</span>Skala pelayanan</h2>
            <div class="scope">
                <div class="scope-item"><strong id="institution-count">0</strong><span>Instansi</span></div>
                <div class="scope-item"><strong id="service-count">0</strong><span>Layanan</span></div>
                <div class="scope-item"><strong id="zone-count">0</strong><span>Zona</span></div>
            </div>
        </article>

        <article class="card">
            <h2 class="card-title"><span class="title-icon">{!! '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18M7 16l4-5 3 3 5-7"/></svg>' !!}</span>Tren pelayanan 7 hari</h2>
            <div class="chart-shell" role="img" aria-label="Grafik tiket diterbitkan selama tujuh hari terakhir">
                <div class="chart" id="trend-chart"></div>
                <div class="chart-labels" id="trend-labels"></div>
            </div>
        </article>

        <article class="card">
            <h2 class="card-title"><span class="title-icon">{!! '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>' !!}</span>Berbagai kebutuhan, satu tempat</h2>
            <div class="services">
                <div class="service"><span class="service-icon">{!! '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3 4 6v5c0 5 3.4 8.4 8 10 4.6-1.6 8-5 8-10V6z"/></svg>' !!}</span><span>Kepolisian</span></div>
                <div class="service"><span class="service-icon">{!! '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V8l7-4 7 4v13M9 12h2m2 0h2m-6 4h2m2 0h2"/></svg>' !!}</span><span>Perizinan</span></div>
                <div class="service"><span class="service-icon">{!! '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6m-6 4h6"/></svg>' !!}</span><span>Pajak</span></div>
                <div class="service"><span class="service-icon">{!! '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21s-8-4.5-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 6.5-8 11-8 11zM9 13h6m-3-3v6"/></svg>' !!}</span><span>Kesehatan</span></div>
                <div class="service"><span class="service-icon">{!! '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3"/><path d="M3 20v-2a6 6 0 0 1 12 0v2m2-9h4m-2-2v4"/></svg>' !!}</span><span>Kependudukan</span></div>
                <div class="service"><span class="service-icon">{!! '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18v12H3zM3 7l9 7 9-7"/></svg>' !!}</span><span>Pos &amp; lainnya</span></div>
            </div>
        </article>

        <article class="card">
            <h2 class="card-title"><span class="title-icon">{!! '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7h6v14m2 0V3h6v18M7 10h2m-2 4h2m6-7h2m-2 4h2m-2 4h2"/></svg>' !!}</span>Instansi pelayanan publik</h2>
            <div class="institutions" id="institutions"></div>
            <p class="more" id="institution-more"></p>
        </article>
    </section>
</main>

<script>
    const numberFormatter = new Intl.NumberFormat('id-ID');
    const initialData = {{ Illuminate\Support\Js::from($dashboardData) }};

    function updateClock() {
        const now = new Date();
        document.getElementById('clock').textContent = `${now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false }).replace('.', ':')} WIB`;
        document.getElementById('date').textContent = now.toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' });
    }

    function render(data) {
        document.getElementById('tickets-today').textContent = numberFormatter.format(data.tickets_today ?? 0);
        document.getElementById('completed-today').textContent = numberFormatter.format(data.completed_today ?? 0);
        document.getElementById('institution-count').textContent = numberFormatter.format(data.institution_count ?? 0);
        document.getElementById('service-count').textContent = numberFormatter.format(data.service_count ?? 0);
        document.getElementById('zone-count').textContent = numberFormatter.format(data.zone_count ?? 0);

        const trend = Array.isArray(data.trend) ? data.trend : [];
        const max = Math.max(1, ...trend.map(item => Number(item.total) || 0));
        document.getElementById('trend-chart').replaceChildren(...trend.map(item => {
            const slot = document.createElement('div');
            const bar = document.createElement('div');
            slot.className = 'bar-slot';
            bar.className = 'bar';
            bar.style.height = `${Math.max(3, ((Number(item.total) || 0) / max) * 100)}%`;
            bar.title = `${item.label}: ${numberFormatter.format(item.total ?? 0)} tiket`;
            slot.appendChild(bar);
            return slot;
        }));
        document.getElementById('trend-labels').replaceChildren(...trend.map(item => {
            const label = document.createElement('span');
            label.textContent = item.label;
            return label;
        }));

        const institutions = Array.isArray(data.institutions) ? data.institutions : [];
        const institutionList = document.getElementById('institutions');
        if (institutions.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'empty';
            empty.textContent = 'Data instansi belum tersedia.';
            institutionList.replaceChildren(empty);
        } else {
            institutionList.replaceChildren(...institutions.map((name, index) => {
                const row = document.createElement('div');
                const number = document.createElement('b');
                const label = document.createElement('span');
                row.className = 'institution';
                number.textContent = String(index + 1).padStart(2, '0');
                label.textContent = name;
                row.append(number, label);
                return row;
            }));
        }

        const remaining = Number(data.remaining_institution_count) || 0;
        document.getElementById('institution-more').textContent = remaining > 0 ? `+ ${numberFormatter.format(remaining)} instansi pelayanan lainnya` : '';
    }

    async function refreshData() {
        try {
            const response = await fetch('{{ route('api.showcase.siola-data') }}', { headers: { Accept: 'application/json' }, cache: 'no-store' });
            if (!response.ok) return;
            render(await response.json());
        } catch (_) {
            // Data terakhir tetap ditampilkan jika jaringan lokal sempat terputus.
        }
    }

    updateClock();
    render(initialData);
    setInterval(updateClock, 1000);
    setInterval(refreshData, 60000);
</script>
</body>
</html>
