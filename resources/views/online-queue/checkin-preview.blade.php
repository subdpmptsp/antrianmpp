<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Check-in Antrean Online — Tahap Pengembangan</title>
    @include('partials.siola-q-favicon')
    <style>
        *{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;background:linear-gradient(145deg,#eaf3ff,#f8fbff);font-family:Inter,ui-sans-serif,system-ui;color:#17233a;padding:20px}.phone{width:min(100%,440px);background:white;border:1px solid #d5e1ef;border-radius:24px;box-shadow:0 24px 70px #123b721c;overflow:hidden}.top{display:flex;align-items:center;justify-content:space-between;padding:16px 18px;border-bottom:1px solid #e5ecf4;color:#175aaa;font-weight:800}.dev{font-size:10px;background:#fff0cf;color:#8a5600;border-radius:999px;padding:6px 8px}.body{padding:24px}.machine{display:inline-flex;gap:7px;align-items:center;background:#eaf8ef;color:#15723c;border-radius:999px;padding:7px 10px;font-size:12px;font-weight:700}.machine i{width:8px;height:8px;border-radius:50%;background:#20a65a}.body h1{font-size:25px;margin:18px 0 7px}.body>p{color:#68778e;font-size:14px;margin:0}.reservation{margin:20px 0;border:1px solid #d8e3f0;border-radius:14px;padding:16px}.reservation small,.reservation strong{display:block}.reservation small{color:#738198}.reservation h2{font-size:18px;margin:7px 0}.details{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:15px}.details div{background:#f5f8fc;padding:11px;border-radius:9px}.details strong{font-size:13px;margin-top:3px}.safe{display:flex;gap:9px;padding:12px;border-radius:10px;background:#eef6ff;color:#174f9f;font-size:12px;margin-bottom:15px}.confirm{width:100%;border:0;border-radius:11px;background:#2367d1;color:#fff;padding:14px;font:inherit;font-weight:800;cursor:pointer}.secondary{width:100%;border:0;background:transparent;color:#56657d;padding:13px;font:inherit}.result{display:none;text-align:center;padding:25px 10px}.result .icon{display:grid;place-items:center;width:60px;height:60px;border-radius:50%;margin:0 auto 14px;background:#daf6e4;color:#14733d;font-size:28px}.result h2{margin:0 0 8px}.result p{color:#68778e}.result strong{display:inline-block;margin-top:6px;background:#fff0cf;color:#8a5600;padding:7px 10px;border-radius:999px;font-size:12px}@media(max-width:440px){body{padding:0;background:white}.phone{border:0;border-radius:0;box-shadow:none;min-height:100vh}.details{grid-template-columns:1fr}}
    </style>
</head>
<body>
<main class="phone">
    <div class="top"><span>SIOLA Q · Check-in</span><span class="dev">Dalam tahap pengembangan</span></div>
    <section class="body" id="confirmation">
        <span class="machine"><i></i> Mesin antrean MPP SIOLA 01</span>
        <h1>Reservasi ditemukan</h1><p>Periksa kembali sebelum mengonfirmasi bahwa Anda sudah tiba.</p>
        <div class="reservation"><small>Kode booking</small><strong>RSV-A7K29</strong><h2>Administrasi Kependudukan</h2><small>Dinas Kependudukan dan Pencatatan Sipil</small><div class="details"><div><small>Tanggal</small><strong>10 September 2026</strong></div><div><small>Sesi</small><strong>09.00–10.00 WIB</strong></div></div></div>
        <div class="safe"><span>✓</span><span>QR mesin berlaku sementara dan hanya digunakan untuk menghubungkan HP ini dengan mesin yang sedang Anda gunakan.</span></div>
        <button class="confirm" id="confirm-button" type="button">Konfirmasi saya sudah tiba</button><button class="secondary" type="button">Bukan reservasi saya</button>
    </section>
    <section class="body result" id="result"><div class="icon">✓</div><h2>Simulasi check-in berhasil</h2><p>Pada versi aktif, mesin akan mencetak nomor antrean reguler setelah konfirmasi ini.</p><strong>Belum ada nomor asli yang diterbitkan</strong></section>
</main>
<script>document.getElementById('confirm-button').addEventListener('click',function(){document.getElementById('confirmation').style.display='none';document.getElementById('result').style.display='block'})</script>
</body>
</html>
