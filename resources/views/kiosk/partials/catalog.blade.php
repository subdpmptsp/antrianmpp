@php
    use Illuminate\Support\Facades\Storage;

    $isLivewire = ($interactionMode ?? 'public') === 'livewire';
    $selectedInstitution = $selectedInstansi
        ? $instansis->firstWhere('instansi_id', $selectedInstansi)
        : null;
    $popularInstitutions = collect($popularInstansis ?? $instansis->take(6));
    $otherInstitutions = collect($otherInstansis ?? $instansis->skip($popularInstitutions->count()));
    $sizePreview = request()->boolean('preview');
@endphp

<div
    class="queue-kiosk"
    data-kiosk-root
    data-mode="{{ $isLivewire ? 'livewire' : 'public' }}"
    data-step="{{ $selectedInstansi ? 2 : 1 }}"
    data-home-url="{{ route('public.queue-kiosk') }}"
>
    <header class="queue-kiosk__header">
        <div class="queue-kiosk__brand">
            <div class="queue-kiosk__logo queue-kiosk__logo--city">
                <img src="{{ $mppBranding['logo_url'] }}" alt="Logo {{ $mppBranding['name'] }}">
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
            <div class="queue-kiosk__logo queue-kiosk__logo--office">
                <img src="{{ asset('img/dpmptsp.png') }}" alt="Logo DPMPTSP Kota Surabaya">
            </div>
        </div>
    </header>

    <main class="queue-kiosk__main">
        <section class="queue-kiosk__content">
            @if (! $selectedInstansi)
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
                    @if ($sizePreview)
                        <div class="queue-kiosk__size-preview" data-kiosk-size-preview>
                            <strong>Mode uji ukuran</strong>
                            <label>Kartu populer <output data-size-output="popular">72</output> px<input type="range" min="56" max="140" value="72" data-size-control="popular"></label>
                            <label>Kartu lainnya <output data-size-output="other">68</output> px<input type="range" min="52" max="120" value="68" data-size-control="other"></label>
                            <label>Logo populer <output data-size-output="popular-logo">46</output> px<input type="range" min="30" max="72" value="46" data-size-control="popular-logo"></label>
                            <label>Logo lainnya <output data-size-output="other-logo">34</output> px<input type="range" min="24" max="56" value="34" data-size-control="other-logo"></label>
                        </div>
                    @endif
                    <div class="queue-kiosk__institution-layout" data-kiosk-institution-grid>
                        <section class="queue-kiosk__institution-section queue-kiosk__institution-section--popular" aria-labelledby="popular-institutions-title">
                            <div class="queue-kiosk__section-heading">
                                <span class="queue-kiosk__section-icon queue-kiosk__section-icon--popular">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 22c4.2 0 7-3 7-7.2 0-3.1-1.8-5.9-5.2-8.8.1 2.5-.8 4.1-2.1 5.1.1-3.7-2-6.8-5.1-9.1.2 4-1.6 6.5-2.1 8.6C3.5 14.6 6.1 22 12 22Z"/></svg>
                                </span>
                                <div><h3 id="popular-institutions-title">Layanan populer</h3><p>Paling sering dikunjungi bulan ini</p></div>
                            </div>
                            <div class="queue-kiosk__popular-list">
                                @foreach ($popularInstitutions as $instansi)
                                    @include('kiosk.partials.institution-card', ['instansi' => $instansi, 'variant' => 'popular', 'isLivewire' => $isLivewire])
                                @endforeach
                            </div>
                        </section>

                        <section class="queue-kiosk__institution-section queue-kiosk__institution-section--others" aria-labelledby="other-institutions-title">
                            <div class="queue-kiosk__section-heading">
                                <span class="queue-kiosk__section-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 21h18M5 21V8l7-4 7 4v13M8 11h2m4 0h2m-8 4h2m4 0h2m-6 6v-3h4v3"/></svg>
                                </span>
                                <div><h3 id="other-institutions-title">Instansi lainnya</h3><p>Diurutkan berdasarkan aktivitas layanan</p></div>
                            </div>
                            <div class="queue-kiosk__other-grid">
                                @forelse ($otherInstitutions as $instansi)
                                    @include('kiosk.partials.institution-card', ['instansi' => $instansi, 'variant' => 'compact', 'isLivewire' => $isLivewire])
                                @empty
                                    <div class="queue-kiosk__section-empty">Semua instansi tersedia pada layanan populer.</div>
                                @endforelse
                            </div>
                        </section>
                    </div>
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

    @if ($isLivewire)
        <div class="queue-kiosk__wire-loading" wire:loading.flex wire:target="selectInstansi,resetSelection">
            <span class="queue-kiosk__spinner"></span>
        </div>
    @endif
</div>
