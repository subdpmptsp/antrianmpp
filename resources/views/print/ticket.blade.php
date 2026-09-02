<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $mppBranding['name'] }} — Tiket {{ $queue->number }}</title>
    <style>
        @page { size: 80mm auto; margin: 3mm; }
        * { box-sizing: border-box; }
        html, body { width: 74mm; margin: 0; padding: 0; }
        body { color: #000; background: #fff; font-family: "Courier New", monospace; text-align: center; }
        .ticket { width: 100%; padding: 2mm 0; }
        .ticket h1 { margin: 0; font-size: 15pt; }
        .ticket p { margin: 1mm 0; font-size: 9pt; }
        .ticket hr { margin: 3mm 0; border: 0; border-top: 1px dashed #000; }
        .ticket .number { margin: 2mm 0; font-size: 30pt; font-weight: 700; letter-spacing: 1px; }
        .ticket .institution { font-size: 11pt; font-weight: 700; }
        .ticket .service { font-size: 9pt; }
        .ticket .meta { margin-top: 3mm; font-size: 8pt; }
    </style>
</head>
<body>
    <main class="ticket">
        <h1>MAL PELAYANAN PUBLIK</h1>
        <p>KOTA SURABAYA</p>
        <hr>
        <p class="institution">{{ $queue->service->instansi?->nama_instansi }}</p>
        <p class="service">{{ $queue->service->name }}</p>
        <div class="number">{{ $queue->number }}</div>
        <p>{{ $queue->service->instansi?->counter?->name }}</p>
        <hr>
        <p class="meta">{{ $queue->created_at->translatedFormat('d F Y') }} · {{ $queue->created_at->format('H:i:s') }}</p>
        <p>Mohon menunggu nomor Anda dipanggil.</p>
    </main>
</body>
</html>
