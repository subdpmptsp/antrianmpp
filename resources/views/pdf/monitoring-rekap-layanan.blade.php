<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Layanan MPP</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 9px; }
        h1 { margin: 0; color: #123f73; font-size: 16px; }
        .meta { margin: 5px 0 12px; color: #5c6779; }
        .summary { margin-bottom: 12px; padding: 8px 10px; background: #eef6ff; border: 1px solid #bfdcff; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #bdc6d2; padding: 5px 6px; vertical-align: middle; }
        th { background: #174b7e; color: white; font-weight: bold; }
        .number { width: 34px; text-align: center; }
        .name { text-align: left; }
        .total { width: 95px; text-align: center; font-weight: bold; }
        .institution td { background: #dceeff; font-weight: bold; }
        .service .name { padding-left: 20px; color: #344054; }
        .footer { margin-top: 8px; color: #6b7280; font-size: 7px; }
    </style>
</head>
<body>
    <h1>Rekap Jumlah Pemohon Mal Pelayanan Publik Kota Surabaya</h1>
    <div class="meta">Periode {{ $from->translatedFormat('d F Y') }} s.d. {{ $to->translatedFormat('d F Y') }} · Seluruh zona</div>
    <div class="summary">
        <strong>{{ $institutions->count() }} instansi</strong> ·
        {{ $institutions->sum(fn ($institution) => $institution->services->count()) }} layanan ·
        <strong>{{ number_format($institutions->sum('period_total'), 0, ',', '.') }} pemohon</strong>
    </div>
    <table>
        <thead><tr><th class="number">No.</th><th class="name">Instansi / Layanan</th><th class="total">Jumlah Pemohon</th></tr></thead>
        <tbody>
            @forelse ($institutions as $institution)
                <tr class="institution"><td class="number">{{ $loop->iteration }}</td><td class="name">{{ $institution->nama_instansi }}</td><td class="total">{{ $institution->period_total }}</td></tr>
                @foreach ($institution->services as $service)
                    <tr class="service"><td class="number"></td><td class="name">↳ {{ $service->prefix }} — {{ $service->name }}</td><td class="total">{{ $service->period_total }}</td></tr>
                @endforeach
            @empty
                <tr><td colspan="3">Belum ada layanan aktif pada periode ini.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Pratinjau dibuat {{ $generatedAt->translatedFormat('d F Y H:i') }} WIB. Unduh melalui penampil PDF browser hanya jika dokumen sudah sesuai.</div>
</body>
</html>
