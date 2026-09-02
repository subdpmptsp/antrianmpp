<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $mppBranding['name'] ?? 'Mal Pelayanan Publik Siola' }} — Loket Panggilan</title>
    @vite(['resources/css/app.css'])
    @filamentStyles
    @livewireStyles
    @stack('styles')
    <style>
        /* Keep the operator workspace readable on wide monitors. */
        .fi-page {
            width: 100%;
            max-width: 1180px;
            margin-inline: auto;
            padding-inline: 1rem;
        }

        .fi-page-content {
            width: 100%;
        }

        .fi-page .queue-kiosk-logo,
        .fi-page img[alt*="Logo"] {
            max-width: 20rem;
            max-height: 20rem;
        }

        @media (min-width: 1280px) {
            .fi-page { padding-inline: 1.5rem; }
        }

        .operator-shell-header {
            max-width: 1180px;
            margin-inline: auto;
            padding: .75rem 1rem 0;
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .operator-logout {
            border: 1px solid #dbe3ef;
            border-radius: .65rem;
            background: #fff;
            color: #334155;
            padding: .45rem .8rem;
            font-size: .8rem;
            font-weight: 600;
            transition: background .15s ease, border-color .15s ease;
        }

        .operator-logout:hover { background: #f8fafc; border-color: #94a3b8; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 font-sans antialiased">
    <header class="operator-shell-header">
        <form method="POST" action="{{ route('filament.admin.auth.logout') }}">
            @csrf
            <button type="submit" class="operator-logout" aria-label="Keluar dari akun">
                Keluar{{ auth()->user()?->name ? ' · '.auth()->user()->name : '' }}
            </button>
        </form>
    </header>
    {{ $slot }}

    @livewire('notifications')
    @filamentScripts
    @livewireScripts
    @stack('scripts')
</body>
</html>
