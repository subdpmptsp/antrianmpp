<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $mppBranding['name'] ?? config('app.name') }} — TV Ruang Tunggu</title>
    @vite(['resources/css/app.css'])
</head>
<body>
    @include('filament.pages.dashboard-kiosk', [
        'tvRouteName' => 'tv.waiting-room',
        'isPublicDisplay' => true,
    ])

    @stack('styles')
    @stack('scripts')
    @if ($selectedZoneIsValid)
        <script>
            window.setInterval(async () => {
                try {
                    const response = await fetch(window.location.href, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        cache: 'no-store',
                    })
                    if (!response.ok) return

                    const nextDocument = new DOMParser().parseFromString(await response.text(), 'text/html')
                    const currentDisplay = document.querySelector('[data-tv-display]')
                    const nextDisplay = nextDocument.querySelector('[data-tv-display]')

                    if (currentDisplay && nextDisplay) currentDisplay.replaceWith(nextDisplay)
                } catch (_) {
                    // TV tetap menampilkan data terakhir ketika koneksi sementara terputus.
                }
            }, 5000)
        </script>
    @endif
</body>
</html>
