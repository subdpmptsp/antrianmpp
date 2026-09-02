<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $mppBranding['name'] ?? config('app.name') }} — TV Ruang Tunggu</title>
    @vite(['resources/css/app.css'])
    @stack('styles')
    @filamentScripts
    @filamentStyles
</head>

<body style="font-family: 'poppins';" class="flex flex-col min-h-screen font-sans antialiased bg-gradient-to-br from-slate-50 via-gray-50 to-blue-50">
    <main>
        {{ $slot }}
    </main>

    @stack('scripts')
    <script>
        // Fullscreen functionality
    const fullscreenBtn = document.getElementById('fullscreen-btn');
    if (fullscreenBtn) {
        fullscreenBtn.addEventListener('click', function() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        });
    }

    </script>
</body>


</html>
