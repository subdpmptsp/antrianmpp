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
    @if (auth()->user()?->isAdmin())
        <a
            href="{{ route('filament.admin.pages.monitoring-dashboard') }}"
            class="admin-sidebar-return"
            aria-label="Kembali ke panel admin dan buka sidebar"
            title="Buka sidebar panel admin"
        >
            <span aria-hidden="true">←</span>
            <span>Panel Admin</span>
        </a>
    @endif
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
    <style>
        .admin-sidebar-return {
            position: fixed;
            z-index: 1000;
            top: 1rem;
            left: 1rem;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            min-height: 44px;
            padding: .65rem .85rem;
            border: 1px solid rgba(255, 255, 255, .45);
            border-radius: .8rem;
            background: rgba(15, 55, 110, .9);
            color: #fff;
            box-shadow: 0 8px 22px rgba(15, 55, 110, .25);
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
        }

        .admin-sidebar-return:hover {
            background: #0b4fa7;
            color: #fff;
        }

        .admin-sidebar-return span:first-child { font-size: 1.2rem; line-height: 1; }

        @media (max-width: 640px) {
            .admin-sidebar-return { top: .65rem; left: .65rem; padding: .6rem .7rem; }
            .admin-sidebar-return span:last-child { display: none; }
        }
    </style>
</body>


</html>
