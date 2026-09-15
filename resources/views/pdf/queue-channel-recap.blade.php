<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Antrean Langsung dan Online</title>
    <style>
        @page { margin: 20px 22px; }
        body { font-family: DejaVu Sans, sans-serif; color: #172033; font-size: 8px; }
        h1 { margin: 0; color: #123f73; font-size: 15px; }
        .meta { margin: 4px 0 11px; color: #5c6779; }
        .summary { margin-bottom: 10px; padding: 7px 9px; border: 1px solid #bfdbfe; background: #eff6ff; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #bcc7d5; padding: 4px 4px; vertical-align: middle; }
        th { background: #174b7e; color: #fff; font-size: 7px; text-align: center; }
        .date { width: 58px; text-align: center; }
        .institution { width: 122px; }
        .prefix { width: 48px; text-align: center; }
        .service { width: 156px; }
        .number { width: 58px; text-align: center; }
        .footer { margin-top: 8px; color: #6b7280; font-size: 7px; }
        .empty { padding: 18px; text-align: center; color: #6b7280; }
    </style>
</head>
<body>
    <h1>Rekap Antrean Langsung &amp; Online</h1>
    <div class="meta">Periode {{ $from->translatedFormat('d F Y') }} s.d. {{ $to->translatedFormat('d F Y') }}</div>
    <div class="summary">
        <strong>{{ $rows->count() }} baris rekap</strong>
        · Layanan: <strong>{{ $serviceName ?: 'Semua layanan' }}</strong>
        · Total nomor terbit: <strong>{{ number_format($rows->sum(fn ($row) => $row[6]), 0, ',', '.') }}</strong>
    </div>
    <table>
        <thead><tr><th class="date">Tanggal</th><th class="institution">Instansi</th><th class="prefix">Prefix</th><th class="service">Layanan</th><th class="number">Langsung</th><th class="number">Online hadir</th><th class="number">Total terbit</th><th class="number">Batal online</th><th class="number">No-show</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td class="date">{{ $row[0] }}</td><td class="institution">{{ $row[1] }}</td><td class="prefix">{{ $row[2] }}</td><td class="service">{{ $row[3] }}</td><td class="number">{{ $row[4] }}</td><td class="number">{{ $row[5] }}</td><td class="number">{{ $row[6] }}</td><td class="number">{{ $row[7] }}</td><td class="number">{{ $row[8] }}</td>
                </tr>
            @empty
                <tr><td class="empty" colspan="9">Belum ada data antrean pada periode dan layanan yang dipilih.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer">Pratinjau dibuat {{ $generatedAt->translatedFormat('d F Y H:i') }} WIB. Angka online hadir dihitung dari nomor antrean yang benar-benar diterbitkan, bukan prefix nomor.</div>
</body>
</html>
