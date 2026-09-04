@php
    $logoUrl = $instansi->logo_path
        ? \Illuminate\Support\Facades\Storage::disk('public')->url($instansi->logo_path)
        : null;
    $cardVariant = $variant === 'popular' ? 'popular' : 'compact';
@endphp

@if ($isLivewire)
    <button
        type="button"
        class="queue-kiosk__institution-card queue-kiosk__institution-card--{{ $cardVariant }}"
        data-kiosk-institution-card
        data-kiosk-navigation
        wire:click="selectInstansi({{ (int) $instansi->instansi_id }})"
    >
@else
    <a
        class="queue-kiosk__institution-card queue-kiosk__institution-card--{{ $cardVariant }}"
        data-kiosk-institution-card
        data-kiosk-navigation
        href="{{ route('public.queue-kiosk', ['instansi' => $instansi->instansi_id]) }}"
    >
@endif
        <span class="queue-kiosk__institution-logo {{ $logoUrl ? 'has-image' : 'is-fallback' }}">
            @if ($logoUrl)
                <img src="{{ $logoUrl }}" alt="Logo {{ $instansi->nama_instansi }}" loading="eager" decoding="async">
            @else
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21h16M6 21V8l6-4 6 4v13M9 10h.01M12 10h.01M15 10h.01M9 14h.01M12 14h.01M15 14h.01M10 21v-3h4v3"/></svg>
            @endif
        </span>
        <span class="queue-kiosk__institution-copy">
            <strong>{{ $instansi->nama_instansi }}</strong>
            <small>{{ $instansi->active_services_count }} layanan tersedia</small>
        </span>
@if ($isLivewire)
    </button>
@else
    </a>
@endif
