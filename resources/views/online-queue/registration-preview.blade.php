<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Antrean Online SIOLA Q — Tahap Pengembangan</title>
    @include('partials.siola-q-favicon')
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f7fc;color:#162238;font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}.top{height:72px;background:white;border-bottom:1px solid #dfe7f1;display:flex;align-items:center;justify-content:space-between;padding:0 max(20px,calc((100% - 1080px)/2))}.brand{display:flex;align-items:center;gap:11px;font-weight:800;color:#174f9f}.brand img{width:42px;height:42px;object-fit:contain}.dev{font-size:12px;background:#fff0cf;color:#8a5600;border-radius:999px;padding:7px 11px;font-weight:700}.wrap{max-width:1080px;margin:0 auto;padding:30px 20px 60px}.intro{text-align:center;margin-bottom:24px}.intro h1{font-size:30px;margin:0 0 8px}.intro p{color:#67758d;margin:0}.grid{display:grid;grid-template-columns:minmax(0,1.15fr) minmax(280px,.65fr);gap:18px}.panel{background:white;border:1px solid #dfe7f1;border-radius:18px;padding:24px;box-shadow:0 10px 32px #163a6b0b}.panel h2{font-size:20px;margin:0 0 5px}.muted{font-size:13px;color:#6b7890}.notice{display:flex;gap:10px;margin:18px 0;padding:13px;border-radius:11px;background:#eef6ff;color:#174f9f;font-size:13px}.formgrid{display:grid;grid-template-columns:1fr 1fr;gap:14px}label{font-size:13px;font-weight:700}label.full{grid-column:1/-1}input,select{display:block;width:100%;margin-top:6px;border:1px solid #cbd6e4;border-radius:10px;padding:12px;background:white;font:inherit;color:#1f2937}input:disabled,select:disabled{background:#f8fafc;color:#64748b}.hint{font-size:11px;color:#72809a;margin-top:5px}.agree{display:flex;align-items:flex-start;gap:8px;font-size:12px;color:#56657d;margin:18px 0}.agree input{width:auto;margin:2px 0}.submit{width:100%;border:0;border-radius:10px;padding:13px;background:#2367d1;color:white;font-size:15px;font-weight:800;cursor:pointer}.steps{display:grid;gap:12px;margin-top:18px}.step{display:grid;grid-template-columns:34px 1fr;gap:10px;align-items:start}.step b{display:grid;place-items:center;width:30px;height:30px;border-radius:50%;background:#e8f1ff;color:#1f63ca}.step strong,.step small{display:block}.step small{color:#738198;margin-top:3px}.booking{margin-top:18px;padding:15px;border:1px dashed #91b7e9;border-radius:12px;background:#f8fbff}.booking strong{font-family:ui-monospace,monospace;color:#174f9f}.footer{text-align:center;color:#7b8799;font-size:12px;margin-top:22px}@media(max-width:760px){.top{height:auto;padding:13px 16px}.grid,.formgrid{grid-template-columns:1fr}label.full{grid-column:auto}.wrap{padding:22px 14px 40px}.panel{padding:18px}.intro h1{font-size:25px}.dev{max-width:145px;text-align:center}}
    </style>
</head>
<body>
<header class="top"><div class="brand"><img src="{{ asset('images/siola-q-icon-192.png') }}" alt="Logo SIOLA Q"><span>SIOLA Q · Antrean Online</span></div><span class="dev">Dalam tahap pengembangan</span></header>
<main class="wrap">
    <div class="intro"><h1>Reservasi kunjungan MPP SIOLA</h1><p>Pilih layanan dan sesi agar waktu kedatangan Anda lebih terencana.</p></div>
    <div class="grid">
        <section class="panel">
            <h2>Data reservasi</h2><div class="muted">Semua kolom bertanda * wajib diisi.</div>
            <div class="notice"><span>🔒</span><span><strong>NIK wajib untuk mencegah reservasi ganda.</strong><br>NIK tidak dicantumkan pada tiket, QR, atau tampilan publik.</span></div>
            <div class="formgrid">
                <label>NIK *<input value="3578••••••••1234" disabled><div class="hint">Terdiri dari 16 digit.</div></label>
                <label>Nama lengkap *<input value="Budi Santoso" disabled></label>
                <label class="full">Layanan *<select disabled><option>Administrasi Kependudukan — Dukcapil</option></select></label>
                <label>Tanggal kedatangan *<input type="text" value="10 September 2026" disabled></label>
                <label>Sesi kedatangan *<select disabled><option>09.00–10.00 · tersisa 6 kuota</option></select></label>
                <label class="full">Nomor WhatsApp *<input value="0812 3456 7890" disabled><div class="hint">Digunakan untuk informasi dan pemulihan kode booking.</div></label>
            </div>
            <label class="agree"><input type="checkbox" checked disabled><span>Saya menyetujui penggunaan data untuk reservasi, pencegahan pendaftaran ganda, dan proses pelayanan.</span></label>
            <button class="submit" type="button" id="preview-submit">Buat reservasi</button>
            <div class="booking" id="preview-message" hidden><span class="muted">Simulasi kode booking</span><br><strong>RSV-A7K29</strong><p class="muted">Belum ada data yang disimpan. Fungsi reservasi masih dalam tahap pengembangan.</p></div>
        </section>
        <aside class="panel">
            <h2>Setelah reservasi</h2><div class="muted">Kode booking bukan nomor antrean.</div>
            <div class="steps"><div class="step"><b>1</b><div><strong>Simpan bukti booking</strong><small>Berisi layanan, tanggal, sesi, dan kode reservasi.</small></div></div><div class="step"><b>2</b><div><strong>Datang sesuai sesi</strong><small>Check-in dibuka menjelang waktu kedatangan.</small></div></div><div class="step"><b>3</b><div><strong>Scan QR pada mesin</strong><small>Gunakan kamera HP atau Google Lens.</small></div></div><div class="step"><b>4</b><div><strong>Konfirmasi dari HP</strong><small>Pastikan layanan dan sesi sudah benar.</small></div></div><div class="step"><b>5</b><div><strong>Ambil tiket tercetak</strong><small>Nomor baru masuk ke antrean reguler setelah check-in.</small></div></div></div>
        </aside>
    </div>
    <div class="footer">Pratinjau frontend · Belum menerima reservasi sebenarnya</div>
</main>
<script>document.getElementById('preview-submit').addEventListener('click',function(){document.getElementById('preview-message').hidden=false;document.getElementById('preview-message').scrollIntoView({behavior:'smooth',block:'nearest'})})</script>
</body>
</html>
