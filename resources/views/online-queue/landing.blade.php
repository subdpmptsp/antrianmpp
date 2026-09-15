<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Pendaftaran antrean online {{ $mppBranding['name'] }} tanpa membuat akun.">
    <title>Antrean Online · SIOLA Q</title>
    @include('partials.siola-q-favicon')
    <style>
        :root { --navy:#073b7a; --blue:#0757b8; --sky:#eaf4ff; --yellow:#ffc400; --yellow-dark:#e0a800; --ink:#10213f; --muted:#5b6b84; --line:#d6e4f5; --green:#16a34a; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin:0; background:#f5f9ff; color:var(--ink); font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif; }
        a { color:inherit; }
        .container { width:min(1280px,calc(100% - 48px)); margin-inline:auto; }
        .site-header { position:sticky; z-index:30; top:0; border-bottom:1px solid var(--line); background:rgba(255,255,255,.97); box-shadow:0 3px 14px rgba(7,59,122,.07); backdrop-filter:blur(12px); }
        .header-inner { min-height:76px; display:flex; align-items:center; justify-content:space-between; gap:26px; }
        .brand { min-width:0; display:flex; align-items:center; gap:10px; text-decoration:none; }
        .brand-logos { display:flex; flex:0 0 auto; align-items:center; gap:6px; }
        .brand-logo,.brand-city-logo { display:block; object-fit:contain; }
        .brand-logo { max-width:155px; }
        .brand-logo--small { height:40px; }.brand-logo--medium { height:48px; }.brand-logo--large { height:56px; }
        .brand-city-logo--small { width:40px;height:40px; }.brand-city-logo--medium { width:48px;height:48px; }.brand-city-logo--large { width:56px;height:56px; }
        .brand-copy { min-width:0; line-height:1.12; }
        .brand-copy small,.brand-copy strong { display:block; }
        .brand-copy small { margin-bottom:3px; color:#48617f; font-size:8px; font-weight:800; letter-spacing:.08em; }
        .brand-copy strong { max-width:260px; overflow:hidden; color:var(--blue); font-size:17px; text-overflow:ellipsis; white-space:nowrap; }
        .main-nav { display:flex; align-items:center; gap:25px; margin-left:auto; }
        .main-nav a { position:relative; padding:28px 0 24px; color:#314764; font-size:13px; font-weight:750; text-decoration:none; }
        .main-nav a:hover,.main-nav a.active { color:var(--blue); }
        .main-nav a.active::after { position:absolute; right:0; bottom:15px; left:0; height:4px; border-radius:4px; background:var(--yellow); content:""; }
        .lookup-top { min-height:44px; display:inline-flex; flex:0 0 auto; align-items:center; justify-content:center; gap:8px; border:2px solid var(--blue); border-radius:12px; padding:10px 19px; color:var(--blue); background:#fff; font-size:13px; font-weight:850; text-decoration:none; transition:.18s ease; }
        .lookup-top:hover { color:#fff; background:var(--blue); }
        .lookup-top svg,.btn svg { width:18px; height:18px; fill:none; stroke:currentColor; stroke-width:2; }

        .hero-band { position:relative; overflow:hidden; background:linear-gradient(135deg,#eaf4ff 0%,#f5faff 56%,#fff 100%); }
        .hero-band::before { position:absolute; width:260px; height:260px; top:-150px; left:max(-90px,calc((100vw - 1280px) / 2 - 170px)); border:45px solid rgba(58,157,232,.12); border-radius:50%; content:""; }
        .hero-band::after { position:absolute; z-index:0; width:150px; height:80px; right:max(18px,calc((100vw - 1280px) / 2 - 20px)); bottom:-42px; border:9px solid var(--yellow); border-radius:50%; opacity:.28; transform:rotate(-10deg); pointer-events:none; content:""; }
        .hero-grid { position:relative; z-index:1; display:grid; grid-template-columns:minmax(420px,1.15fr) minmax(290px,.7fr) minmax(280px,.75fr); align-items:center; gap:24px; }
        .hero-copy { padding:52px 0 48px; }
        .eyebrow { margin:0 0 12px; color:var(--blue); font-size:12px; font-weight:900; letter-spacing:.18em; }
        .hero-copy h1 { max-width:620px; margin:0; color:var(--ink); font-size:clamp(40px,4.6vw,64px); line-height:1.02; letter-spacing:-.045em; }
        .hero-copy h1 span { display:block; }
        .hero-copy h1 em { color:var(--yellow); font-style:normal; text-shadow:0 2px 0 rgba(143,104,0,.13); white-space:nowrap; }
        .lead { max-width:630px; margin:16px 0 0; color:#526d90; font-size:15px; line-height:1.65; }
        .actions { display:flex; flex-wrap:wrap; gap:12px; margin-top:23px; }
        .btn { min-height:47px; display:inline-flex; align-items:center; justify-content:center; gap:9px; border:2px solid #1c6ed5; border-radius:10px; padding:10px 19px; color:#1359b2; background:#fff; font-size:13px; font-weight:900; text-decoration:none; }
        .btn-primary { border-color:var(--yellow); color:var(--ink); background:var(--yellow); box-shadow:0 9px 22px rgba(224,168,0,.25); }
        .btn-primary:hover { border-color:var(--yellow-dark); background:var(--yellow-dark); }
        .btn-disabled { cursor:not-allowed; border-color:#cdd8e5; color:#708198; background:#e8eef4; box-shadow:none; }
        .micro-steps { display:flex; flex-wrap:wrap; gap:12px 22px; margin-top:21px; color:#405978; font-size:12px; }
        .micro-steps span { display:flex; align-items:center; gap:7px; }
        .micro-steps b { width:32px; height:32px; display:grid; place-items:center; border-radius:50%; color:#fff; background:var(--blue); box-shadow:0 4px 10px rgba(7,87,184,.2); }
        .status-card { width:100%; border:1px solid var(--line); border-radius:24px; padding:25px; background:rgba(255,255,255,.97); box-shadow:0 20px 48px rgba(7,59,122,.16); }
        .status-head { display:flex; align-items:center; justify-content:space-between; gap:10px; }
        .status-head h2 { margin:0; font-size:19px; }
        .badge { flex:0 0 auto; border-radius:999px; padding:7px 12px; color:#0b7246; background:#dcf5e7; font-size:11px; font-weight:850; }
        .badge.off { color:#687991; background:#e9eef4; }
        .status-row { display:grid; grid-template-columns:22px 1fr auto; gap:9px; align-items:center; border-bottom:1px solid #e7eef5; padding:16px 0; color:#425b7d; font-size:13px; }
        .status-row svg { width:17px; fill:none; stroke:#2776dc; stroke-width:2; }
        .status-row strong { color:#173761; }
        .status-note { display:flex; gap:10px; margin-top:17px; border-radius:14px; padding:15px; color:#31547a; background:var(--sky); font-size:12px; line-height:1.55; }
        .status-note b { width:18px; height:18px; display:grid; flex:0 0 auto; place-items:center; border-radius:50%; color:#fff; background:#66a7e4; }
        .hero-photo { min-height:330px; align-self:stretch; background-position:center; background-size:cover; -webkit-mask-image:linear-gradient(90deg,transparent 0%,rgba(0,0,0,.86) 18%,#000 40%,#000 86%,transparent 100%); mask-image:linear-gradient(90deg,transparent 0%,rgba(0,0,0,.86) 18%,#000 40%,#000 86%,transparent 100%); filter:saturate(1.08) brightness(1.07); }
        .hero-photo--small { min-height:280px; }.hero-photo--medium { min-height:330px; }.hero-photo--large { min-height:380px; }

        .content { padding:40px 0 52px; }
        .section-heading { display:flex; align-items:end; justify-content:space-between; gap:20px; margin-bottom:15px; }
        .section-heading h2 { margin:0; font-size:28px; letter-spacing:-.025em; }
        .section-heading p { margin:5px 0 0; color:var(--muted); font-size:13px; }
        .section-link { color:#1763c5; font-size:11px; font-weight:850; text-decoration:none; }
        .how { display:grid; grid-template-columns:230px 1fr; gap:30px; align-items:start; }
        .steps-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; }
        .step-card { position:relative; min-height:116px; display:grid; grid-template-columns:54px 1fr; gap:15px; align-items:center; overflow:hidden; border:1px solid var(--line); border-radius:16px; padding:20px; background:#fff; box-shadow:0 9px 22px rgba(7,59,122,.07); transition:.18s ease; }
        .step-card::before { position:absolute; top:0; right:0; left:0; height:4px; background:var(--yellow); content:""; }
        .step-card:hover { transform:translateY(-2px); box-shadow:0 13px 28px rgba(7,59,122,.11); }
        .step-no { position:absolute; width:25px; height:25px; top:10px; left:10px; display:grid; place-items:center; border-radius:50%; color:#fff; background:#4799e7; font-size:11px; font-weight:900; }
        .icon-box { width:52px; height:52px; display:grid; place-items:center; border-radius:14px; color:var(--blue); background:var(--sky); }
        .icon-box svg { width:25px; height:25px; fill:none; stroke:currentColor; stroke-width:1.8; }
        .step-card strong,.step-card small { display:block; }
        .step-card strong { font-size:14px; }.step-card small { margin-top:5px; color:var(--muted); font-size:11px; line-height:1.5; }
        .services-section { margin-top:38px; }
        .service-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
        .service-card { min-width:0; border:1px solid var(--line); border-radius:16px; padding:19px; background:#fff; box-shadow:0 8px 21px rgba(7,59,122,.06); }
        .service-top { display:grid; grid-template-columns:43px minmax(0,1fr) auto; gap:10px; align-items:start; }
        .service-card h3 { overflow:hidden; margin:1px 0 0; font-size:14px; line-height:1.35; text-overflow:ellipsis; white-space:nowrap; }
        .service-card p { overflow:hidden; margin:5px 0 0; color:var(--muted); font-size:11px; text-overflow:ellipsis; white-space:nowrap; }
        .service-meta { display:flex; align-items:center; gap:7px; margin:17px 0; color:#245b9e; font-size:12px; font-weight:750; }
        .service-button { min-height:41px; display:flex; align-items:center; justify-content:center; border-radius:10px; color:var(--ink); background:var(--yellow); font-size:12px; font-weight:900; text-decoration:none; transition:.18s ease; }
        .service-button:hover { background:var(--yellow-dark); }
        .service-button.off { color:#77869a; background:#e2e8ef; }
        .lower-grid { display:grid; grid-template-columns:1fr 1.15fr; gap:24px; margin-top:31px; }
        .important { border:1px solid var(--yellow); border-radius:16px; padding:22px; color:#334b69; background:#fff4cc; }
        .important-head { display:flex; gap:11px; align-items:center; margin-bottom:10px; color:#153762; font-size:13px; font-weight:900; }
        .warning { width:42px; height:42px; display:grid; place-items:center; border-radius:11px; color:var(--ink); background:var(--yellow); font-size:18px; }
        .important ul { margin:0; padding-left:53px; font-size:12px; line-height:1.75; }
        .faq-head { display:flex; align-items:center; justify-content:space-between; gap:15px; margin-bottom:9px; }
        .faq-head h2 { margin:0; font-size:16px; }
        details { border:1px solid var(--line); border-radius:12px; background:#fff; box-shadow:0 5px 14px rgba(7,59,122,.04); }
        details + details { margin-top:9px; }
        summary { cursor:pointer; padding:14px 16px; color:var(--ink); font-size:13px; font-weight:800; list-style:none; }
        summary::-webkit-details-marker { display:none; }
        summary::after { float:right; content:"⌄"; }
        details[open] summary::after { content:"⌃"; }
        details p { margin:0; border-top:1px solid #edf1f6; padding:13px 16px; color:var(--muted); font-size:12px; line-height:1.6; }
        footer { border-top:4px solid var(--yellow); color:#fff; background:linear-gradient(90deg,var(--blue),var(--navy)); }
        .footer-inner { min-height:78px; display:flex; align-items:center; justify-content:space-between; gap:24px; font-size:12px; }
        .footer-help { display:flex; flex-wrap:wrap; gap:9px 20px; }

        @media (max-width:1120px) {
            .main-nav { display:none; }
            .hero-grid { grid-template-columns:minmax(0,1.1fr) minmax(280px,.9fr); }
            .hero-photo { display:none; }
            .hero-band::after { right:-28px; opacity:.2; }
            .service-grid { grid-template-columns:repeat(2,1fr); }
        }
        @media (max-width:760px) {
            .container { width:min(100% - 28px,620px); }
            .header-inner { min-height:62px; gap:10px; }
            .brand { gap:7px; }.brand-logo { max-width:92px; height:32px; }.brand-city-logo { width:31px;height:31px; }
            .brand-copy small { display:none; }.brand-copy strong { max-width:130px; font-size:12px; }
            .lookup-top { min-height:38px; padding:7px 10px; font-size:10px; }.lookup-top span { display:none; }
            .hero-grid { display:flex; flex-direction:column; gap:0; padding-bottom:22px; }
            .hero-copy { width:100%; padding:33px 0 23px; }
            .hero-copy h1 { font-size:38px; }.lead { font-size:14px; }
            .actions { display:grid; grid-template-columns:1fr; }.btn { width:100%; }
            .micro-steps { display:grid; grid-template-columns:1fr; gap:8px; }
            .status-card { width:100%; }
            .how { grid-template-columns:1fr; gap:10px; }.steps-grid { grid-template-columns:1fr; }
            .section-heading { align-items:start; }.section-heading h2 { font-size:21px; }
            .service-grid,.lower-grid { grid-template-columns:1fr; }
            .footer-inner { align-items:flex-start; flex-direction:column; padding:16px 0; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a class="brand" href="#beranda" aria-label="Beranda Antrean Online">
                <span class="brand-logos">
                    @if ($mppBranding['landing_city_logo_url'])
                        <img class="brand-city-logo brand-city-logo--{{ $mppBranding['landing_city_logo_size'] }}" src="{{ $mppBranding['landing_city_logo_url'] }}" alt="Logo Pemerintah Kota Surabaya">
                    @endif
                    <img class="brand-logo brand-logo--{{ $mppBranding['landing_logo_size'] }}" src="{{ $mppBranding['landing_logo_url'] }}" alt="Logo {{ $mppBranding['name'] }}">
                </span>
                <span class="brand-copy"><small>PEMERINTAH KOTA SURABAYA</small><strong>{{ $mppBranding['name'] }}</strong></span>
            </a>
            <nav class="main-nav" aria-label="Navigasi utama">
                <a class="active" href="#beranda">Beranda</a>
                <a href="#cara-kerja">Cara kerja</a>
                <a href="#layanan">Layanan</a>
                <a href="#informasi">Informasi</a>
                <a href="#kontak">Kontak</a>
            </nav>
            <a class="lookup-top" href="{{ route('online-queue.lookup') }}">
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <span>Cari Reservasi Saya</span>
            </a>
        </div>
    </header>

    <main>
        <section class="hero-band" id="beranda">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <p class="eyebrow">ANTREAN ONLINE MPP SIOLA</p>
                    <h1><span>Ambil antrean</span><span>sebelum datang ke</span><em>MPP Siola</em></h1>
                    <p class="lead">Pilih layanan publik yang Anda butuhkan dan tentukan sesi kedatangan secara online. Simpan tiket digital, lalu lakukan check-in di kiosk saat tiba.</p>
                    <div class="actions">
                        @if ($bookingAvailable)
                            <a class="btn btn-primary" href="{{ route('online-queue.registration') }}">Ambil Antrean Sekarang <span aria-hidden="true">→</span></a>
                        @else
                            <span class="btn btn-disabled">Antrean Online Belum Tersedia</span>
                        @endif
                        <a class="btn" href="{{ route('online-queue.lookup') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                            Cari Reservasi Saya
                        </a>
                    </div>
                    <div class="micro-steps"><span><b>1</b>Pilih layanan</span><span><b>2</b>Simpan tiket digital</span><span><b>3</b>Check-in di MPP</span></div>
                </div>

                <aside class="status-card" aria-label="Status antrean hari ini">
                    <div class="status-head"><h2>Status antrean hari ini</h2><span class="badge {{ $todayAvailable ? '' : 'off' }}">{{ $todayAvailable ? 'Tersedia' : 'Belum tersedia' }}</span></div>
                    <div class="status-row">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 10h18"/></svg>
                        <span>Jam layanan</span><strong>{{ $todayHours ? str_replace('–', ' – ', $todayHours).' WIB' : '—' }}</strong>
                    </div>
                    <div class="status-row">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        <span>Layanan aktif</span><strong>{{ $todayServiceCount }} layanan</strong>
                    </div>
                    <div class="status-note"><b>i</b><span>{{ $todayAvailable ? 'Pilih layanan dan sesi yang masih memiliki kuota.' : 'Saat ini antrean online belum aktif. Silakan cek kembali sesuai jadwal pelayanan.' }}</span></div>
                </aside>

                <div class="hero-photo hero-photo--{{ $mppBranding['landing_hero_image_size'] }}" role="img" aria-label="Gedung atau fasilitas MPP" style="background-image:linear-gradient(180deg,rgba(111,190,245,.08),rgba(4,72,139,.14)),url('{{ $mppBranding['landing_hero_image_url'] }}')"></div>
            </div>
        </section>

        <div class="container content">
            <section class="how" id="cara-kerja">
                <div class="section-heading"><div><h2>Cara kerja</h2><p>Mudah dan praktis dalam 3 langkah</p></div></div>
                <div class="steps-grid">
                    <article class="step-card"><b class="step-no">1</b><span class="icon-box"><svg viewBox="0 0 24 24"><rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8 8h8M8 12h8M8 16h5"/></svg></span><div><strong>Pilih layanan</strong><small>Pilih kebutuhan dan sesi kedatangan.</small></div></article>
                    <article class="step-card"><b class="step-no">2</b><span class="icon-box"><svg viewBox="0 0 24 24"><path d="M5 5h14v4a3 3 0 0 0 0 6v4H5v-4a3 3 0 0 0 0-6z"/></svg></span><div><strong>Simpan tiket digital</strong><small>Dapatkan QR dan kode booking.</small></div></article>
                    <article class="step-card"><b class="step-no">3</b><span class="icon-box"><svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></span><div><strong>Check-in saat tiba</strong><small>Aktifkan nomor antrean melalui kiosk MPP.</small></div></article>
                </div>
            </section>

            <section class="services-section" id="layanan">
                <div class="section-heading"><div><h2>Layanan tersedia</h2><p>Pilih layanan dengan jadwal dan kuota yang masih tersedia.</p></div>@if($bookingAvailable)<a class="section-link" href="{{ route('online-queue.registration') }}">Lihat semua layanan →</a>@endif</div>
                <div class="service-grid">
                    @forelse ($services as $index => $service)
                        <article class="service-card">
                            <div class="service-top"><span class="icon-box"><svg viewBox="0 0 24 24"><path d="M4 21h16M6 21V8l6-4 6 4v13M9 11h.01M12 11h.01M15 11h.01M10 21v-4h4v4"/></svg></span><div><h3 title="{{ $service['name'] }}">{{ $service['name'] }}</h3><p title="{{ $service['institution'] }}">{{ $service['institution'] }}</p></div><span class="badge {{ $service['available_today'] ? '' : 'off' }}">{{ $service['available_today'] ? 'Hari ini' : 'Terjadwal' }}</span></div>
                            <div class="service-meta">Kuota tersisa: {{ $service['remaining'] }} · {{ $service['sessions'] }} sesi</div>
                            <a class="service-button" href="{{ route('online-queue.registration') }}">Ambil antrean →</a>
                        </article>
                    @empty
                        @foreach (range(1,4) as $placeholder)
                            <article class="service-card"><div class="service-top"><span class="icon-box"><svg viewBox="0 0 24 24"><path d="M4 21h16M6 21V8l6-4 6 4v13M10 21v-4h4v4"/></svg></span><div><h3>Belum tersedia</h3><p>Jadwal layanan akan ditampilkan di sini.</p></div><span class="badge off">Nonaktif</span></div><div class="service-meta">Kuota tersisa: —</div><span class="service-button off">Segera hadir</span></article>
                        @endforeach
                    @endforelse
                </div>
            </section>

            <div class="lower-grid" id="informasi">
                <section class="important">
                    <div class="important-head"><span class="warning">!</span>Informasi penting</div>
                    <ul><li>Satu NIK hanya dapat memiliki satu reservasi aktif pada layanan dan tanggal yang sama.</li><li>Reservasi belum menjadi nomor antrean sebelum check-in di lokasi.</li><li>Datang sesuai sesi yang dipilih; keterlambatan dapat membatalkan reservasi.</li><li>Tiket dapat dibuka kembali melalui menu “Cari Reservasi Saya”.</li></ul>
                </section>
                <section aria-label="Pertanyaan yang sering diajukan">
                    <div class="faq-head"><h2>Pertanyaan yang sering ditanyakan (FAQ)</h2></div>
                    <details><summary>Apakah antrean online ini gratis?</summary><p>Ya. Pendaftaran antrean online MPP tidak dipungut biaya dan tidak memerlukan pembuatan akun.</p></details>
                    <details><summary>Kapan saya harus melakukan check-in?</summary><p>Datang sesuai sesi yang dipilih dan lakukan check-in melalui kiosk MPP sesuai rentang waktu yang berlaku.</p></details>
                    <details><summary>Bagaimana jika saya tidak bisa datang sesuai jadwal?</summary><p>Buka kembali tiket melalui Cari Reservasi Saya, lalu batalkan reservasi agar kuota dapat digunakan pemohon lain.</p></details>
                </section>
            </div>
        </div>
    </main>

    <footer id="kontak">
        <div class="container footer-inner"><div class="footer-help"><span>Butuh bantuan? Hubungi petugas informasi {{ $mppBranding['name'] }}.</span>@if($mppBranding['phone'])<span>☎ {{ $mppBranding['phone'] }}</span>@endif<span>{{ $mppBranding['address'] }}</span></div><span>© {{ now()->year }} SIOLA Q · Antrean Online MPP</span></div>
    </footer>
</body>
</html>
