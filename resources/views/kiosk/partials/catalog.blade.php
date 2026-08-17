@php
    $isLivewire = ($interactionMode ?? 'public') === 'livewire';
    $currentStep = ! $selectedCounter ? 1 : (! $selectedInstansi ? 2 : 3);
    $selectedZoneName = $selectedCounter
        ? ($counters[$selectedCounter]['name'] ?? 'Zona '.$selectedCounter)
        : null;
    $selectedInstitution = $selectedInstansi
        ? $instansis->firstWhere('instansi_id', $selectedInstansi)
        : null;
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
                <span class="queue-kiosk__eyebrow">Pemerintah Kota Surabaya</span>
                <h1>Mal Pelayanan Publik Siola</h1>
                <p>Mesin pengambilan nomor antrian</p>
            </div>
        </div>

        <div class="queue-kiosk__header-tools">
            <div class="queue-kiosk__clock" aria-label="Waktu saat ini">
                <strong data-kiosk-clock>--:--</strong>
                <span data-kiosk-date>Memuat tanggal...</span>
            </div>

            <button class="queue-kiosk__icon-button" type="button" data-kiosk-fullscreen aria-label="Tampilkan layar penuh">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M8 3H5a2 2 0 0 0-2 2v3m13-5h3a2 2 0 0 1 2 2v3M8 21H5a2 2 0 0 1-2-2v-3m13 5h3a2 2 0 0 0 2-2v-3"/>
                </svg>
            </button>

            <div class="queue-kiosk__logo queue-kiosk__logo--office">
                <img src="{{ asset('img/dpmptsp.png') }}" alt="Logo DPMPTSP Kota Surabaya">
            </div>
        </div>
    </header>

    <main class="queue-kiosk__main">
        @if (session('error'))
            <div class="queue-kiosk__alert" role="alert">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M12 9v4m0 4h.01M10.3 4.3 2.7 17.5A1.7 1.7 0 0 0 4.2 20h15.6a1.7 1.7 0 0 0 1.5-2.5L13.7 4.3a2 2 0 0 0-3.4 0Z"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <nav class="queue-kiosk__steps" aria-label="Tahapan pengambilan antrian">
            @foreach ([1 => ['Zona', 'Pilih area'], 2 => ['Instansi', 'Pilih tujuan'], 3 => ['Layanan', 'Cetak tiket']] as $number => [$label, $description])
                <div class="queue-kiosk__step {{ $currentStep >= $number ? 'is-active' : '' }} {{ $currentStep > $number ? 'is-complete' : '' }}">
                    <span class="queue-kiosk__step-number">
                        @if ($currentStep > $number)
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/></svg>
                        @else
                            {{ $number }}
                        @endif
                    </span>
                    <span>
                        <strong>{{ $label }}</strong>
                        <small>{{ $description }}</small>
                    </span>
                </div>
            @endforeach
        </nav>

        <section class="queue-kiosk__content">
            @if (! $selectedCounter)
                <div class="queue-kiosk__intro">
                    <span class="queue-kiosk__section-kicker">Langkah 1 dari 3</span>
                    <h2>Pilih area layanan</h2>
                    <p>Sentuh zona yang memuat instansi tujuan Anda.</p>
                </div>

                <div class="queue-kiosk__zone-grid">
                    @forelse ($counters as $zoneId => $zone)
                        @php
                            $institutions = collect($zone['services'] ?? [])->filter()->values();
                            $zoneNumber = preg_replace('/\D+/', '', (string) ($zone['name'] ?? $zoneId)) ?: $zoneId;
                        @endphp

                        @if ($isLivewire)
                            <button type="button" class="queue-kiosk__zone-card" wire:click="selectCounter({{ (int) $zoneId }})">
                        @else
                            <a class="queue-kiosk__zone-card" href="{{ route('public.queue-kiosk', ['zona' => $zoneId]) }}">
                        @endif
                                <span class="queue-kiosk__zone-number">{{ str_pad((string) $zoneNumber, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="queue-kiosk__zone-heading">
                                    <strong>{{ strtoupper($zone['name'] ?? 'Zona '.$zoneId) }}</strong>
                                    <small>{{ $institutions->count() }} instansi tersedia</small>
                                </span>
                                <span class="queue-kiosk__zone-list">
                                    @foreach ($institutions->take(3) as $institution)
                                        <span>{{ is_array($institution) ? ($institution['nama_service'] ?? $institution['name'] ?? '-') : $institution }}</span>
                                    @endforeach
                                    @if ($institutions->count() > 3)
                                        <em>+{{ $institutions->count() - 3 }} instansi lainnya</em>
                                    @endif
                                </span>
                                <span class="queue-kiosk__card-action">
                                    Pilih zona
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                </span>
                        @if ($isLivewire)
                            </button>
                        @else
                            </a>
                        @endif
                    @empty
                        <div class="queue-kiosk__empty">
                            <h3>Zona belum tersedia</h3>
                            <p>Silakan hubungi petugas layanan.</p>
                        </div>
                    @endforelse
                </div>
            @elseif (! $selectedInstansi)
                <div class="queue-kiosk__toolbar">
                    @if ($isLivewire)
                        <button type="button" class="queue-kiosk__back" wire:click="resetSelection">
                    @else
                        <a class="queue-kiosk__back" href="{{ route('public.queue-kiosk') }}">
                    @endif
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                            Kembali
                    @if ($isLivewire)</button>@else</a>@endif

                    <span class="queue-kiosk__selection-pill">{{ strtoupper($selectedZoneName) }}</span>
                </div>

                <div class="queue-kiosk__intro">
                    <span class="queue-kiosk__section-kicker">Langkah 2 dari 3</span>
                    <h2>Pilih instansi tujuan</h2>
                    <p>Sentuh nama instansi yang ingin Anda kunjungi.</p>
                </div>

                <div class="queue-kiosk__institution-grid">
                    @forelse ($instansis as $instansi)
                        @if ($isLivewire)
                            <button type="button" class="queue-kiosk__institution-card" wire:click="selectInstansi({{ (int) $instansi->instansi_id }})">
                        @else
                            <a class="queue-kiosk__institution-card" href="{{ route('public.queue-kiosk', ['zona' => $selectedCounter, 'instansi' => $instansi->instansi_id]) }}">
                        @endif
                                <span class="queue-kiosk__institution-icon">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 21h16M6 21V8l6-4 6 4v13M9 10h.01M12 10h.01M15 10h.01M9 14h.01M12 14h.01M15 14h.01M10 21v-3h4v3"/></svg>
                                </span>
                                <span class="queue-kiosk__institution-name">{{ $instansi->nama_instansi }}</span>
                                <span class="queue-kiosk__institution-arrow">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                                </span>
                        @if ($isLivewire)</button>@else</a>@endif
                    @empty
                        <div class="queue-kiosk__empty">
                            <h3>Instansi belum tersedia</h3>
                            <p>Kembali dan pilih zona lainnya atau hubungi petugas.</p>
                        </div>
                    @endforelse
                </div>
            @else
                <div class="queue-kiosk__toolbar">
                    @if ($instansis->count() > 1)
                        @if ($isLivewire)
                            <button type="button" class="queue-kiosk__back" wire:click="resetInstansi">
                        @else
                            <a class="queue-kiosk__back" href="{{ route('public.queue-kiosk', ['zona' => $selectedCounter]) }}">
                        @endif
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                Kembali
                        @if ($isLivewire)</button>@else</a>@endif
                    @else
                        @if ($isLivewire)
                            <button type="button" class="queue-kiosk__back" wire:click="resetSelection">
                        @else
                            <a class="queue-kiosk__back" href="{{ route('public.queue-kiosk') }}">
                        @endif
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
                                Kembali
                        @if ($isLivewire)</button>@else</a>@endif
                    @endif

                    <div class="queue-kiosk__selection-summary">
                        <span>{{ strtoupper($selectedZoneName) }}</span>
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                        <strong>{{ $selectedInstitution?->nama_instansi ?? 'Instansi' }}</strong>
                    </div>
                </div>

                <div class="queue-kiosk__intro">
                    <span class="queue-kiosk__section-kicker">Langkah 3 dari 3</span>
                    <h2>Pilih layanan</h2>
                    <p>Periksa pilihan Anda, lalu cetak nomor antrian.</p>
                </div>

                <div class="queue-kiosk__service-grid">
                    @forelse ($services as $service)
                        @if (! $isLivewire)
                            <form
                                id="kiosk-service-{{ $service->id }}"
                                method="POST"
                                action="{{ route('public.queue-kiosk.select-service', ['serviceId' => $service->id, 'zona' => $selectedCounter]) }}"
                                class="queue-kiosk__service-form"
                            >
                                @csrf
                                <input type="hidden" name="queue_request_token" value="{{ $queueRequestToken }}">
                            </form>
                        @endif

                        <button
                            type="button"
                            class="queue-kiosk__service-card"
                            data-kiosk-service
                            data-service-id="{{ $service->id }}"
                            data-service-name="{{ $service->name ?? $service->nama_service ?? 'Layanan' }}"
                            data-institution-name="{{ $selectedInstitution?->nama_instansi ?? 'Instansi' }}"
                            @if (! $isLivewire) data-form-id="kiosk-service-{{ $service->id }}" @endif
                        >
                            <span class="queue-kiosk__service-icon">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6zM18 12h.01"/></svg>
                            </span>
                            <span class="queue-kiosk__service-copy">
                                <strong>{{ $service->name ?? $service->nama_service ?? '-' }}</strong>
                                <small>Sentuh untuk mengambil nomor</small>
                            </span>
                            <span class="queue-kiosk__service-action">Cetak tiket</span>
                        </button>
                    @empty
                        <div class="queue-kiosk__empty">
                            <h3>Layanan belum tersedia</h3>
                            <p>Silakan pilih instansi lain atau hubungi petugas.</p>
                        </div>
                    @endforelse
                </div>
            @endif
        </section>
    </main>

    <footer class="queue-kiosk__footer">
        <span class="queue-kiosk__connection">
            <i data-kiosk-online-dot></i>
            <span data-kiosk-online-text>Sistem siap digunakan</span>
        </span>
        <span>Butuh bantuan? Silakan hubungi petugas di area layanan.</span>
    </footer>

    <div class="queue-kiosk__loading" data-kiosk-loading aria-live="polite" aria-hidden="true">
        <span class="queue-kiosk__spinner"></span>
        <strong>Sedang menyiapkan tiket...</strong>
        <small>Mohon tunggu dan jangan sentuh layar.</small>
    </div>

    <dialog class="queue-kiosk__dialog" data-kiosk-dialog>
        <div class="queue-kiosk__dialog-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/></svg>
        </div>
        <span class="queue-kiosk__section-kicker">Konfirmasi pilihan</span>
        <h2>Cetak nomor antrian?</h2>
        <dl>
            <div><dt>Instansi</dt><dd data-kiosk-dialog-institution>-</dd></div>
            <div><dt>Layanan</dt><dd data-kiosk-dialog-service>-</dd></div>
        </dl>
        <div class="queue-kiosk__dialog-actions">
            <button type="button" class="queue-kiosk__dialog-cancel" data-kiosk-dialog-cancel>Periksa kembali</button>
            <button type="button" class="queue-kiosk__dialog-confirm" data-kiosk-dialog-confirm>
                Ya, cetak tiket
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>
    </dialog>

    @if ($isLivewire)
        <div class="queue-kiosk__wire-loading" wire:loading.flex wire:target="selectCounter,selectInstansi,selectService,resetInstansi,resetSelection">
            <span class="queue-kiosk__spinner"></span>
        </div>
    @endif
</div>
