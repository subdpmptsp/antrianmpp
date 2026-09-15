@php
    use Illuminate\Support\Facades\Storage;

    $isLivewire = ($interactionMode ?? 'public') === 'livewire';
    $selectedInstitution = $selectedInstansi
        ? $instansis->firstWhere('instansi_id', $selectedInstansi)
        : null;
    $kioskInstitutionEntries = collect($kioskInstitutionEntries ?? []);
    $bpjsInstansis = collect($bpjsInstansis ?? []);
    $bpjsDirectServices = collect($bpjsDirectServices ?? []);
    $showBpjsChoices = (bool) ($showBpjsChoices ?? false);
    $showOnlineCheckin = (bool) ($showOnlineCheckin ?? false);
@endphp

<div
    class="queue-kiosk"
    data-kiosk-root
    data-mode="{{ $isLivewire ? 'livewire' : 'public' }}"
    data-step="{{ ($selectedInstansi || $showOnlineCheckin) ? 2 : 1 }}"
    data-home-url="{{ route('public.queue-kiosk') }}"
>
    <header class="queue-kiosk__header">
        <div class="queue-kiosk__brand">
            <div class="queue-kiosk__logo queue-kiosk__logo--city queue-kiosk__logo--{{ $mppBranding['kiosk_logo_size'] }}">
                <img src="{{ $mppBranding['kiosk_logo_url'] }}" alt="Logo {{ $mppBranding['name'] }}">
            </div>
            <div class="queue-kiosk__brand-copy">
                <span>Pemerintah Kota Surabaya</span>
                <h1>{{ $mppBranding['name'] }}</h1>
                <p>Mesin pengambilan nomor antrian</p>
            </div>
        </div>

        <div class="queue-kiosk__header-tools">
            <div class="queue-kiosk__clock" aria-label="Waktu saat ini">
                <strong data-kiosk-clock>--:--</strong>
                <span data-kiosk-date>Memuat tanggal...</span>
            </div>
            <button class="queue-kiosk__fullscreen" type="button" data-kiosk-fullscreen aria-label="Tampilkan layar penuh">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3H5a2 2 0 0 0-2 2v3m13-5h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3m13 5h3a2 2 0 0 0 2-2v-3"/></svg>
            </button>
            <div class="queue-kiosk__logo queue-kiosk__logo--office queue-kiosk__logo--{{ $mppBranding['kiosk_office_logo_size'] }}">
                <img src="{{ $mppBranding['kiosk_office_logo_url'] }}" alt="Logo instansi pendamping kiosk">
            </div>
        </div>
    </header>

    <main class="queue-kiosk__main">
        <section class="queue-kiosk__content">
            @if ($showOnlineCheckin)
                <div class="queue-kiosk__toolbar">
                    <a class="queue-kiosk__back" data-kiosk-navigation href="{{ route('public.queue-kiosk') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                        Kembali ke daftar instansi
                    </a>
                    <div class="queue-kiosk__selected-institution">
                        <span><small>Menu terpilih</small><strong>Check-in Antrean Online</strong></span>
                    </div>
                </div>

                <div class="queue-kiosk__intro">
                    <h2>Check-in antrean online</h2>
                    <p>Pindai QR menggunakan HP untuk mengaktifkan reservasi dan mencetak tiket.</p>
                </div>

                @if (! ($onlineCheckinEnabled ?? false))
                    <div class="queue-kiosk__empty">
                        <h3>Belum ada antrean online aktif.</h3>
                        <p>QR check-in tidak ditampilkan. Silakan kembali ke daftar instansi.</p>
                    </div>
                @else
                    <div class="queue-kiosk__online-checkin" data-online-checkin
                        data-status-url="{{ route('online-queue.checkin.status', $onlineCheckinToken) }}">
                        <div class="queue-kiosk__online-copy" data-online-checkin-waiting>
                            <h3>Pindai untuk check-in</h3>
                            <p>Gunakan kamera HP atau Google Lens. Siapkan kode booking dan 4 digit terakhir NIK.</p>
                            <ol>
                                <li><b>1</b><span>Pindai QR pada layar ini.</span></li>
                                <li><b>2</b><span>Konfirmasi data reservasi melalui HP.</span></li>
                                <li><b>3</b><span>Ambil tiket yang dicetak mesin.</span></li>
                            </ol>
                            <div class="queue-kiosk__online-note">QR berlaku singkat dan hanya menerima satu check-in.</div>
                        </div>
                        <div class="queue-kiosk__online-result" data-online-checkin-result hidden>
                            <strong>Check-in berhasil</strong>
                            <div data-online-checkin-number></div>
                            <p>Mohon tunggu, tiket sedang dicetak.</p>
                        </div>
                        <div class="queue-kiosk__online-qr">
                            <div class="queue-kiosk__online-qr-box">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(220)->margin(1)->generate(route('online-queue.checkin.phone', $onlineCheckinToken)) !!}</div>
                            <strong data-online-checkin-timer>Berlaku 01:30</strong>
                            <p>QR akan diperbarui otomatis setelah waktunya habis.</p>
                        </div>
                    </div>
                @endif
            @elseif (! $selectedInstansi)
                @if ($showBpjsChoices)
                    <div class="queue-kiosk__intro">
                        <h2>Pilih layanan BPJS</h2>
                        <p>Sentuh jenis layanan BPJS yang Anda butuhkan.</p>
                    </div>
                    <div class="queue-kiosk__bpjs-back">
                        @if ($isLivewire)
                            <button type="button" wire:click="resetSelection">Kembali ke daftar instansi</button>
                        @else
                            <a data-kiosk-navigation href="{{ route('public.queue-kiosk') }}">Kembali ke daftar instansi</a>
                        @endif
                    </div>
                    <div class="queue-kiosk__institution-grid queue-kiosk__institution-grid--bpjs">
                        @forelse ($bpjsInstansis as $instansi)
                            @php
                                $directService = $bpjsDirectServices->get($instansi->instansi_id);
                                $isDirectServiceAvailable = (bool) $directService?->getAttribute('queue_available');
                            @endphp
                            @if (! $isLivewire && $directService)
                                <form id="kiosk-bpjs-service-{{ $directService->id }}" method="POST" action="{{ route('public.queue-kiosk.select-service', ['serviceId' => $directService->id]) }}" class="queue-kiosk__service-form">
                                    @csrf
                                    <input type="hidden" name="queue_request_token" value="{{ $queueRequestToken }}">
                                    <input type="hidden" name="instansi_id" value="{{ $instansi->instansi_id }}">
                                </form>
                                <button
                                    type="button"
                                    class="queue-kiosk__institution-card queue-kiosk__institution-card--catalog {{ $isDirectServiceAvailable ? '' : 'is-unavailable' }}"
                                    data-kiosk-service
                                    data-form-id="kiosk-bpjs-service-{{ $directService->id }}"
                                    @disabled(! $isDirectServiceAvailable)
                                >
                                    @if ($instansi->logo_path)
                                        <span class="queue-kiosk__institution-logo has-image"><img src="{{ Storage::disk('public')->url($instansi->logo_path) }}" alt="Logo {{ $instansi->nama_instansi }}" loading="eager" decoding="async"></span>
                                    @else
                                        <span class="queue-kiosk__institution-logo is-fallback"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21h16M6 21V8l6-4 6 4v13M9 10h.01M12 10h.01M15 10h.01M9 14h.01M12 14h.01M15 14h.01M10 21v-3h4v3"/></svg></span>
                                    @endif
                                    <span class="queue-kiosk__institution-copy"><strong>{{ $instansi->nama_instansi }}</strong><small>{{ $isDirectServiceAvailable ? 'Sentuh untuk cetak tiket' : $directService->getAttribute('queue_unavailable_message') }}</small></span>
                                </button>
                            @else
                                @include('kiosk.partials.institution-card', ['instansi' => $instansi, 'variant' => 'catalog', 'isLivewire' => $isLivewire])
                            @endif
                        @empty
                            <div class="queue-kiosk__empty"><h3>Layanan BPJS belum tersedia</h3><p>Silakan hubungi petugas layanan.</p></div>
                        @endforelse
                    </div>
                @else
                <div class="queue-kiosk__intro">
                    <h2>Instansi apa yang Anda tuju?</h2>
                    <p>Sentuh salah satu pilihan di bawah ini.</p>
                </div>

                @if ($instansis->isEmpty())
                    <div class="queue-kiosk__empty">
                        <h3>Instansi belum tersedia</h3>
                        <p>Silakan hubungi petugas layanan.</p>
                    </div>
                @else
                    <div class="queue-kiosk__institution-grid" data-kiosk-institution-grid>
                        @foreach ($kioskInstitutionEntries as $entry)
                            @if ($entry['type'] === 'bpjs')
                                @if ($isLivewire)
                                    <button type="button" class="queue-kiosk__institution-card queue-kiosk__institution-card--catalog" wire:click="selectBpjs">
                                @else
                                    <a class="queue-kiosk__institution-card queue-kiosk__institution-card--catalog" data-kiosk-navigation href="{{ route('public.queue-kiosk', ['bpjs' => 1]) }}">
                                @endif
                                        <span class="queue-kiosk__institution-logo is-fallback"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 16.875h3.375m0 0h3.375m-3.375 0V13.5m0 3.375v3.375M6 10.5h2.25a2.25 2.25 0 0 0 2.25-2.25V6a2.25 2.25 0 0 0-2.25-2.25H6A2.25 2.25 0 0 0 3.75 6v2.25A2.25 2.25 0 0 0 6 10.5Zm0 9.75h2.25A2.25 2.25 0 0 0 10.5 18v-2.25a2.25 2.25 0 0 0-2.25-2.25H6a2.25 2.25 0 0 0-2.25 2.25V18A2.25 2.25 0 0 0 6 20.25Zm9.75-9.75H18a2.25 2.25 0 0 0 2.25-2.25V6A2.25 2.25 0 0 0 18 3.75h-2.25A2.25 2.25 0 0 0 13.5 6v2.25a2.25 2.25 0 0 0 2.25 2.25Z"/></svg></span>
                                        <span class="queue-kiosk__institution-copy"><strong>BPJS</strong><small>Pilih jenis layanan</small></span>
                                @if ($isLivewire)</button>@else</a>@endif
                            @else
                                @php
                                    $instansi = $instansis->firstWhere('instansi_id', $entry['instansi_id']);
                                @endphp
                                @if ($instansi)
                                    @include('kiosk.partials.institution-card', ['instansi' => $instansi, 'variant' => 'catalog', 'isLivewire' => $isLivewire])
                                @endif
                            @endif
                        @endforeach

                        <a
                            class="queue-kiosk__institution-card queue-kiosk__institution-card--catalog"
                            data-kiosk-navigation
                            href="{{ route('public.queue-kiosk', ['online_checkin' => 1]) }}"
                            aria-label="Buka check-in antrean online"
                        >
                            <span class="queue-kiosk__institution-logo is-fallback">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.5A.75.75 0 0 1 4.5 3.75h4.125a.75.75 0 0 1 .75.75v4.125a.75.75 0 0 1-.75.75H4.5a.75.75 0 0 1-.75-.75V4.5Zm10.875 0a.75.75 0 0 1 .75-.75H19.5a.75.75 0 0 1 .75.75v4.125a.75.75 0 0 1-.75.75h-4.125a.75.75 0 0 1-.75-.75V4.5ZM3.75 15.375a.75.75 0 0 1 .75-.75h4.125a.75.75 0 0 1 .75.75V19.5a.75.75 0 0 1-.75.75H4.5a.75.75 0 0 1-.75-.75v-4.125Zm10.875-.75h2.25v2.25h-2.25v-2.25Zm3.375 0h2.25v2.25H18v-2.25Zm-3.375 3.375h2.25v2.25h-2.25V18Zm3.375 0h2.25v2.25H18V18Z"/>
                                </svg>
                            </span>
                            <span class="queue-kiosk__institution-copy">
                                <strong>Check-in Antrean Online</strong>
                                <small>Pindai QR booking</small>
                            </span>
                        </a>
                    </div>
                @endif
                @endif

            @else
                <div class="queue-kiosk__toolbar">
                    @if ($isLivewire)
                        <button type="button" class="queue-kiosk__back" wire:click="resetSelection">
                    @else
                        <a class="queue-kiosk__back" data-kiosk-navigation href="{{ route('public.queue-kiosk') }}">
                    @endif
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            Ganti instansi
                    @if ($isLivewire)</button>@else</a>@endif

                    <div class="queue-kiosk__selected-institution">
                        @if ($selectedInstitution?->logo_path)
                            <img src="{{ Storage::disk('public')->url($selectedInstitution->logo_path) }}" alt="">
                        @endif
                        <span><small>Instansi terpilih</small><strong>{{ $selectedInstitution?->nama_instansi }}</strong></span>
                    </div>
                </div>

                <div class="queue-kiosk__intro">
                    <h2>Pilih layanan yang dibutuhkan</h2>
                    <p>Tiket akan langsung dicetak setelah layanan disentuh.</p>
                </div>

                <div class="queue-kiosk__service-grid {{ $services->count() === 1 ? 'is-single' : '' }}">
                    @php
                        $sharedConsultationServices = collect($services)
                            ->filter(fn ($item): bool => in_array($item->prefix, ['3C-6', '3C-7'], true))
                            ->values();
                        $sharedConsultationService = $sharedConsultationServices
                            ->first(fn ($item): bool => (bool) $item->getAttribute('queue_available'))
                            ?? $sharedConsultationServices
                            ->first(fn ($item): bool => (bool) $item->getAttribute('is_recommended_consultation_counter'))
                            ?? $sharedConsultationServices->first();
                        $sharedBpjsServices = collect($services)
                            ->filter(fn ($item): bool => in_array($item->prefix, ['4A1', '4A2'], true))
                            ->values();
                        $sharedBpjsService = $sharedBpjsServices
                            ->first(fn ($item): bool => (bool) $item->getAttribute('queue_available'))
                            ?? $sharedBpjsServices->first();
                    @endphp

                    @if ($sharedConsultationService)
                        @php
                            $sharedAccepting = $sharedConsultationServices->contains(fn ($item): bool => (bool) $item->getAttribute('queue_available'));
                            $sharedWaiting = (int) $sharedConsultationServices->sum(fn ($item): int => (int) ($item->active_queue_count ?? 0));
                            $sharedClosedMessage = $sharedConsultationServices->first()?->getAttribute('queue_unavailable_message') ?: 'Layanan ini sedang tidak menerima nomor antrean.';
                        @endphp
                        @if (! $isLivewire)
                            <form id="kiosk-service-shared-consultation" method="POST" action="{{ route('public.queue-kiosk.select-service', ['serviceId' => $sharedConsultationService->id]) }}" class="queue-kiosk__service-form">
                                @csrf
                                <input type="hidden" name="queue_request_token" value="{{ $queueRequestToken }}">
                                <input type="hidden" name="instansi_id" value="{{ $selectedInstansi }}">
                            </form>
                        @endif
                        <button type="button"
                            class="queue-kiosk__service-card {{ $sharedAccepting ? '' : 'is-unavailable' }}"
                            data-kiosk-service
                            data-service-id="{{ $sharedConsultationService->id }}"
                            data-queue-closed="{{ $sharedAccepting ? 'false' : 'true' }}"
                            @if (! $isLivewire) data-form-id="kiosk-service-shared-consultation" @endif
                            @disabled(! $sharedAccepting)
                        >
                            <span class="queue-kiosk__service-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5h-2M6 14h12v7H6z"/></svg></span>
                            <span class="queue-kiosk__service-copy">
                                <strong>Konsultasi Kependudukan</strong>
                                <small>{{ $sharedAccepting ? $sharedWaiting.' pemohon menunggu · Loket 3C-6 / 3C-7' : $sharedClosedMessage }}</small>
                            </span>
                            <svg class="queue-kiosk__arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    @endif

                    @if ($sharedBpjsService)
                        @php
                            $sharedBpjsAccepting = $sharedBpjsServices->contains(fn ($item): bool => (bool) $item->getAttribute('queue_available'));
                            $sharedBpjsWaiting = (int) $sharedBpjsServices->sum(fn ($item): int => (int) ($item->active_queue_count ?? 0));
                            $sharedBpjsClosedMessage = $sharedBpjsServices->first()?->getAttribute('queue_unavailable_message') ?: 'Layanan ini sedang tidak menerima nomor antrean.';
                        @endphp
                        @if (! $isLivewire)
                            <form id="kiosk-service-shared-bpjs" method="POST" action="{{ route('public.queue-kiosk.select-service', ['serviceId' => $sharedBpjsService->id]) }}" class="queue-kiosk__service-form">
                                @csrf
                                <input type="hidden" name="queue_request_token" value="{{ $queueRequestToken }}">
                                <input type="hidden" name="instansi_id" value="{{ $selectedInstansi }}">
                            </form>
                        @endif
                        <button type="button"
                            class="queue-kiosk__service-card {{ $sharedBpjsAccepting ? '' : 'is-unavailable' }}"
                            data-kiosk-service
                            data-service-id="{{ $sharedBpjsService->id }}"
                            data-queue-closed="{{ $sharedBpjsAccepting ? 'false' : 'true' }}"
                            @if (! $isLivewire) data-form-id="kiosk-service-shared-bpjs" @endif
                            @disabled(! $sharedBpjsAccepting)
                        >
                            <span class="queue-kiosk__service-copy">
                                <strong>{{ $sharedBpjsService->name }}</strong>
                                <small>{{ $sharedBpjsAccepting ? $sharedBpjsWaiting.' pemohon menunggu · Loket 4A1 / 4A2' : $sharedBpjsClosedMessage }}</small>
                            </span>
                            <svg class="queue-kiosk__arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    @endif

                    @forelse ($services as $service)
                        @php
                            $isAcceptingQueues = (bool) $service->getAttribute('queue_available');
                            $queueUnavailableMessage = (string) ($service->getAttribute('queue_unavailable_message') ?: 'Layanan ini sedang tidak menerima nomor antrean.');
                            $isDisdukcapilConsultationCounter = (bool) $service->getAttribute('is_disdukcapil_consultation_counter');
                            $isRecommendedConsultationCounter = (bool) $service->getAttribute('is_recommended_consultation_counter');
                        @endphp
                        @if ($isDisdukcapilConsultationCounter || in_array($service->prefix, ['4A1', '4A2'], true))
                            @continue
                        @endif
                        @if (! $isLivewire)
                            <form
                                id="kiosk-service-{{ $service->id }}"
                                method="POST"
                                action="{{ route('public.queue-kiosk.select-service', ['serviceId' => $service->id]) }}"
                                class="queue-kiosk__service-form"
                            >
                                @csrf
                                <input type="hidden" name="queue_request_token" value="{{ $queueRequestToken }}">
                                <input type="hidden" name="instansi_id" value="{{ $selectedInstansi }}">
                            </form>
                        @endif

                        <button
                            type="button"
                            class="queue-kiosk__service-card {{ $isAcceptingQueues ? '' : 'is-unavailable' }}"
                            data-kiosk-service
                            data-service-id="{{ $service->id }}"
                            data-queue-closed="{{ $isAcceptingQueues ? 'false' : 'true' }}"
                            @if (! $isLivewire) data-form-id="kiosk-service-{{ $service->id }}" @endif
                            @disabled(! $isAcceptingQueues)
                        >
                            <span class="queue-kiosk__service-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6zM18 12h.01"/></svg>
                            </span>
                            <span class="queue-kiosk__service-copy">
                                <strong>{{ $isDisdukcapilConsultationCounter ? "Loket {$service->prefix}" : $service->name }}</strong>
                                @if ($isDisdukcapilConsultationCounter)
                                    <small>
                                        Konsultasi Kependudukan · {{ $service->active_queue_count }} pemohon menunggu
                                    </small>
                                    @if ($isRecommendedConsultationCounter)
                                        <span class="queue-kiosk__recommendation">Disarankan</span>
                                    @endif
                                @else
                                    <small>
                                        @if ($isAcceptingQueues)
                                            @if ((int) ($service->active_queue_count ?? 0) > 0)
                                                {{ (int) $service->active_queue_count }} pemohon menunggu
                                            @else
                                                Belum ada antrean menunggu
                                            @endif
                                        @else
                                            {{ $queueUnavailableMessage }}
                                        @endif
                                    </small>
                                @endif
                            </span>
                            <svg class="queue-kiosk__arrow" viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        </button>
                    @empty
                        <div class="queue-kiosk__empty">
                            <h3>Layanan belum tersedia</h3>
                            <p>Silakan pilih instansi lainnya atau hubungi petugas.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </section>
    </main>

    <footer class="queue-kiosk__footer">
        <span>Butuh bantuan? Silakan hubungi petugas.</span>
        <span class="queue-kiosk__connection"><i></i><strong data-kiosk-online-text>Sistem siap digunakan</strong></span>
    </footer>

    <div class="queue-kiosk__loading" data-kiosk-loading aria-live="polite" aria-hidden="true">
        <span class="queue-kiosk__printer-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/></svg>
        </span>
        <strong>Sedang mencetak</strong>
        <span>Mohon ambil tiket Anda</span>
        <small>Jangan tekan layar kembali.</small>
    </div>

    <div class="queue-kiosk__error" data-kiosk-error hidden role="alert">
        <span>!</span>
        <strong>Mesin cetak belum siap</strong>
        <p data-kiosk-error-message>Silakan hubungi petugas.</p>
        <button type="button" data-kiosk-error-home>Kembali</button>
    </div>

    @if ($kioskBreak ?? false)
        <section class="queue-kiosk__break-modal" role="alertdialog" aria-modal="true" aria-labelledby="kiosk-break-title" data-kiosk-break-until="{{ $kioskBreak['ends_at']->toIso8601String() }}" data-kiosk-countdown-label="Buka kembali dalam">
            <div class="queue-kiosk__break-modal-card">
                <span class="queue-kiosk__break-modal-icon" aria-hidden="true">◷</span>
                <p>MPP SIOLA</p>
                <h2 id="kiosk-break-title">{{ $kioskOperationalMessage['title'] }}</h2>
                <strong>{{ $kioskOperationalMessage['body'] }}</strong>
                <span>Pelayanan dan pengambilan nomor dibuka kembali pukul {{ $kioskBreak['ends_at']->format('H.i') }} WIB.</span>
                <div class="queue-kiosk__break-countdown" data-kiosk-break-countdown>Memuat waktu…</div>
                <small>{{ $kioskOperationalMessage['footer'] }}</small>
            </div>
        </section>
    @elseif ($kioskPreOpening ?? false)
        <section class="queue-kiosk__break-modal" role="alertdialog" aria-modal="true" aria-labelledby="kiosk-pre-opening-title" data-kiosk-break-until="{{ $kioskPreOpening['opens_at']->toIso8601String() }}" data-kiosk-countdown-label="Dimulai dalam">
            <div class="queue-kiosk__break-modal-card">
                <img class="queue-kiosk__closure-logo" src="{{ $mppBranding['logo_url'] }}" alt="Logo {{ $mppBranding['name'] }}">
                <p>MPP SIOLA</p>
                <h2 id="kiosk-pre-opening-title">{{ $kioskOperationalMessage['title'] }}</h2>
                <strong>{{ $kioskOperationalMessage['body'] }}</strong>
                <div class="queue-kiosk__break-countdown" data-kiosk-break-countdown>Memuat waktu…</div>
                <small>{{ $kioskOperationalMessage['footer'] }}</small>
            </div>
        </section>
    @elseif ($kioskOperationalClosure ?? false)
        <section class="queue-kiosk__break-modal" role="alertdialog" aria-modal="true" aria-labelledby="kiosk-closure-title" data-kiosk-operational-closure>
            <div class="queue-kiosk__break-modal-card">
                <img class="queue-kiosk__closure-logo" src="{{ $mppBranding['logo_url'] }}" alt="Logo {{ $mppBranding['name'] }}">
                <p>MPP SIOLA</p>
                <h2 id="kiosk-closure-title">{{ $kioskOperationalMessage['title'] }}</h2>
                <strong>{{ $kioskOperationalMessage['body'] }}</strong>
                <span>Pelayanan kembali dibuka</span>
                <div class="queue-kiosk__break-countdown">
                    {{ $kioskOperationalClosure['opens_at']->locale('id')->translatedFormat('l') }} · pukul {{ $kioskOperationalClosure['opens_at']->format('H.i') }} WIB
                </div>
                <small>{{ $kioskOperationalMessage['footer'] }}</small>
            </div>
        </section>
    @endif

    @if ($isLivewire)
        <div class="queue-kiosk__wire-loading" wire:loading.flex wire:target="selectInstansi,resetSelection">
            <span class="queue-kiosk__spinner"></span>
        </div>
    @endif
</div>
