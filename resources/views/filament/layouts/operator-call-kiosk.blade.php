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

        .admin-sidebar-return {
            position: fixed;
            z-index: 1000;
            top: .75rem;
            left: .75rem;
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            min-height: 42px;
            padding: .55rem .75rem;
            border: 1px solid #c9d8ec;
            border-radius: .7rem;
            background: #fff;
            color: #174a8c;
            box-shadow: 0 6px 18px rgba(15, 55, 110, .12);
            font-size: .8rem;
            font-weight: 700;
            text-decoration: none;
        }

        .admin-sidebar-return:hover { background: #edf5ff; color: #0b4fa7; }
        .admin-sidebar-return span:first-child { font-size: 1.15rem; line-height: 1; }

        @media (max-width: 640px) {
            .admin-sidebar-return { top: .55rem; left: .55rem; }
            .admin-sidebar-return span:last-child { display: none; }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-50 font-sans antialiased">
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
