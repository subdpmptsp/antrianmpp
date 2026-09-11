<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Check-in Antrean Online — Kiosk SIOLA Q</title>
    @include('partials.siola-q-favicon')
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:#f5f8fc;color:#15233a;font-family:Inter,ui-sans-serif,system-ui;display:grid;grid-template-rows:auto 1fr}.top{height:78px;background:white;border-bottom:1px solid #dce5f0;display:flex;align-items:center;justify-content:space-between;padding:0 34px}.brand{font-size:20px;color:#174f9f;font-weight:800}.dev{background:#fff0cf;color:#8a5600;border-radius:999px;padding:8px 12px;font-size:12px;font-weight:800}.main{display:grid;place-items:center;padding:28px}.panel{width:min(100%,920px);display:grid;grid-template-columns:1fr 1fr;background:white;border:1px solid #dce5f0;border-radius:24px;overflow:hidden;box-shadow:0 18px 60px #153b6910}.copy{padding:46px}.copy h1{font-size:34px;margin:0 0 10px}.copy>p{font-size:17px;color:#68778e}.steps{display:grid;gap:16px;margin:30px 0}.step{display:flex;align-items:center;gap:12px}.step b{display:grid;place-items:center;width:34px;height:34px;border-radius:50%;background:#e5f0ff;color:#1d5fc5}.manual{padding:16px;background:#f5f8fc;border-radius:12px;color:#596980;font-size:14px}.qrside{background:#eaf3ff;padding:40px;display:grid;place-items:center;text-align:center}.qr{background:white;border-radius:18px;padding:20px;box-shadow:0 8px 28px #15447a18}.qr svg{display:block}.timer{margin-top:18px;color:#174f9f;font-weight:800}.qrside p{color:#68778e;max-width:320px}.cancel{border:1px solid #bac8db;border-radius:10px;background:white;padding:12px 20px;font:inherit;font-weight:700;color:#44536a;margin-top:10px}@media(max-width:750px){.top{padding:0 16px}.panel{grid-template-columns:1fr}.copy{padding:26px}.copy h1{font-size:27px}.qrside{padding:28px}}
    </style>
</head>
<body>
<header class="top"><span class="brand">SIOLA Q · Mesin Antrean</span><span class="dev">Dalam tahap pengembangan</span></header>
<main class="main"><section class="panel"><div class="copy"><h1>Check-in antrean online</h1><p>Gunakan HP Anda untuk menghubungkan reservasi dengan mesin ini.</p><div class="steps"><div class="step"><b>1</b><span>Buka kamera HP atau Google Lens.</span></div><div class="step"><b>2</b><span>Pindai QR yang tampil di sebelah kanan.</span></div><div class="step"><b>3</b><span>Konfirmasi reservasi dari HP Anda.</span></div><div class="step"><b>4</b><span>Ambil nomor antrean yang dicetak mesin.</span></div></div><div class="manual"><strong>HP atau kamera bermasalah?</strong><br>Pada versi aktif tersedia input kode booking dan bantuan petugas.</div></div><div class="qrside"><div><div class="qr">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(230)->margin(1)->generate(route('online-queue.preview.checkin', 'SIMULASI-MESIN-01')) !!}</div><div class="timer">QR simulasi · 00:45</div><p>Pindai untuk membuka pratinjau halaman konfirmasi. Belum ada data atau nomor antrean yang dibuat.</p><button class="cancel" type="button">Kembali</button></div></div></section></main>
</body>
</html>
