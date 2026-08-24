@php
    use Illuminate\Support\Facades\Storage;

    $isLivewire = ($interactionMode ?? 'public') === 'livewire';
    $currentStep = $selectedInstansi ? 2 : 1;
    $selectedInstitution = $selectedInstansi
        ? $instansis->firstWhere('instansi_id', $selectedInstansi)
        : null;
    $popularInstitutions = collect($popularInstansis ?? $instansis->take(6));
    $otherInstitutions = collect($otherInstansis ?? $instansis->skip($popularInstitutions->count()));
@endphp

<div
    class="queue-kiosk"
    data-kiosk-root
    data-mode="{{ $isLivewire ? 'livewire' : 'public' }}"
    data-step="{{ $currentStep }}"
    data-home-url="{{ route('public.queue-kiosk') }}"
>
    <header class="queue-kiosk__header">
        <div class="queue-kiosk__brand">
            <div class="queue-kiosk__logo queue-kiosk__logo--city">
                <img src="{{ asset('img/logopemkot_white.png') }}" alt="Logo Pemerintah Kota Surabaya">
            </div>
            <div class="queue-kiosk__brand-copy">
                <span>Pemerintah Kota Surabaya</span>
                <h1>Mal Pelayanan Publik Siola</h1>
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
        <nav class="queue-kiosk__steps" aria-label="Tahapan pengambilan antrian">
            @foreach ([1 => ['Instansi', 'Pilih tujuan'], 2 => ['Layanan', 'Cetak langsung']] as $number => [$label, $description])
                <div class="queue-kiosk__step {{ $currentStep >= $number ? 'is-active' : '' }} {{ $currentStep > $number ? 'is-complete' : '' }}">
                    <span class="queue-kiosk__step-number">
                        @if ($currentStep > $number)
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                        @else
                            {{ $number }}
                        @endif
                    </span>
                    <span><strong>{{ $label }}</strong><small>{{ $description }}</small></span>
                </div>
            @endforeach
        </nav>

        <section class="queue-kiosk__content">
            @if (! $selectedInstansi)
                <div class="queue-kiosk__intro">
                    <span>Langkah 1 dari 2</span>
                    <h2>Instansi apa yang Anda tuju?</h2>
                    <p>Sentuh salah satu pilihan di bawah ini.</p>
                </div>

                @if ($instansis->isEmpty())
                    <div class="queue-kiosk__empty">
                        <h3>Instansi belum tersedia</h3>
                        <p>Silakan hubungi petugas layanan.</p>
                    </div>
                @else
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
                        <a class="queue-kiosk__back" href="{{ route('public.queue-kiosk') }}">
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
                    <span>Langkah 2 dari 2</span>
                    <h2>Pilih layanan yang dibutuhkan</h2>
                    <p>Tiket akan langsung dicetak setelah layanan disentuh.</p>
                </div>

                <div class="queue-kiosk__service-grid">
                    @forelse ($services as $service)
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
                            class="queue-kiosk__service-card"
                            data-kiosk-service
                            data-service-id="{{ $service->id }}"
                            @if (! $isLivewire) data-form-id="kiosk-service-{{ $service->id }}" @endif
                        >
                            <span class="queue-kiosk__service-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6zM18 12h.01"/></svg>
                            </span>
                            <span class="queue-kiosk__service-copy">
                                <strong>{{ $service->name }}</strong>
                                <small>Sentuh untuk mencetak tiket</small>
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
