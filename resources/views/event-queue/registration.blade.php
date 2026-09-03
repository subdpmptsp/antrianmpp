<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $event->name }} — Pendaftaran Antrean</title>
    <style>
        :root{font-family:Inter,Arial,sans-serif;color:#18233a;background:#f2f6fc}*{box-sizing:border-box}body{margin:0}.wrap{max-width:640px;margin:0 auto;padding:32px 18px}.brand{color:#1f5fca;font-weight:800;letter-spacing:.08em;font-size:.78rem}.card{background:white;border:1px solid #dbe5f3;border-radius:20px;padding:28px;box-shadow:0 15px 40px #173b7514}h1{margin:8px 0;font-size:clamp(1.55rem,5vw,2.25rem)}p{color:#64748b;line-height:1.55}.meta{display:flex;gap:8px;flex-wrap:wrap;margin:20px 0}.badge{padding:7px 10px;border-radius:99px;background:#e7f0ff;color:#1858b9;font-size:.85rem;font-weight:700}label{display:block;font-size:.9rem;font-weight:700;margin:17px 0 7px}input{width:100%;border:1px solid #c9d7eb;border-radius:10px;padding:13px;font:inherit}button{width:100%;border:0;background:#1967d2;color:white;padding:14px;border-radius:10px;font:700 1rem inherit;margin-top:22px;cursor:pointer}.alert{padding:12px;border-radius:10px;background:#fff1f1;color:#b42318;margin:12px 0}.closed{padding:18px;border-radius:12px;background:#fff7dd;color:#8a5b00;font-weight:700}.footer{text-align:center;color:#94a3b8;font-size:.78rem;margin-top:22px}
    </style>
</head>
<body><main class="wrap"><div class="brand">SIOLA Q · ANTREAN ONLINE</div><section class="card"><h1>{{ $event->name }}</h1>@if($event->description)<p>{{ $event->description }}</p>@endif
    <div class="meta"><span class="badge">Kuota {{ $event->daily_quota }} peserta</span>@if($event->starts_at)<span class="badge">{{ $event->starts_at->translatedFormat('d M Y · H:i') }} WIB</span>@endif</div>
    @if(! $event->isAcceptingRegistrations())<div class="closed">Pendaftaran untuk event ini sedang tidak dibuka.</div>@else
        @if($errors->any())<div class="alert">{{ $errors->first() }}</div>@endif
        <form method="post" action="{{ route('event.register', $event->public_token) }}">@csrf
            <label for="name">Nama lengkap</label><input id="name" name="name" value="{{ old('name') }}" required maxlength="150" autocomplete="name">
            <label for="nik">NIK</label><input id="nik" name="nik" value="{{ old('nik') }}" required inputmode="numeric" minlength="16" maxlength="16" autocomplete="off">
            <label for="phone">Nomor WhatsApp</label><input id="phone" name="phone" value="{{ old('phone') }}" required inputmode="tel" maxlength="32" autocomplete="tel">
            <button type="submit">Daftar & Ambil Tiket</button>
        </form>
    @endif
    </section><div class="footer">Data digunakan khusus untuk layanan event ini.</div></main></body></html>
