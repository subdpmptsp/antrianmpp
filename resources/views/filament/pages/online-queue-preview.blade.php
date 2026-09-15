<x-filament-panels::page>
    @php
        $onlineSettings = $this->onlineSettings;
        $hasActivePublicSessions = $this->hasActivePublicSessions;
        $dashboardReservationStats = $this->dashboardReservationStats;
        $onlinePublicReady = $onlineSettings->global_enabled && $onlineSettings->regular_enabled && $hasActivePublicSessions;
        $onlineUnavailableMessage = ! $onlineSettings->global_enabled || ! $onlineSettings->regular_enabled
            ? 'Pendaftaran online belum diaktifkan secara global.'
            : 'Saat ini belum ada antrean online yang berjalan.';
        $tabs = [
            'dashboard' => 'Dashboard',
            'sessions' => 'Jadwal & Kuota',
            'participants' => 'Pendaftar',
            'checkin' => 'Check-in',
            'links' => 'Tautan & QR',
            'settings' => 'Integrasi API',
        ];
    @endphp

    <div class="oq-page">
        <section class="oq-assurance">
            <x-heroicon-o-check-circle class="h-6 w-6 shrink-0" />
            <div>
                <strong>Satu pusat kendali untuk antrean online</strong>
                <p>Atur jadwal mingguan, kuota, dan periode Online Penuh tanpa membuat modul terpisah.</p>
            </div>
        </section>

        <header class="oq-header">
            <div>
                <h1>Antrean Online</h1>
                <p>Kelola layanan harian, reservasi, check-in, dan pengalihan kanal onsite secara terjadwal.</p>
            </div>
            <span @class(['oq-global-status', 'is-enabled' => $onlineSettings->global_enabled])><i></i> {{ $onlineSettings->global_enabled ? 'Pendaftaran global aktif' : 'Konfigurasi global belum diaktifkan' }}</span>
        </header>

        <div class="oq-toolbar">
            <div class="oq-section-title"><x-heroicon-o-building-storefront class="h-6 w-6" /><div><strong>Layanan Harian</strong><span>Jadwal rutin dan jadwal khusus berada di tempat yang sama.</span></div></div>
        </div>

        <nav class="oq-tabs" aria-label="Bagian Antrean Online">
            @foreach ($tabs as $key => $label)
                @if ($key === 'settings')
                    <a href="{{ \App\Filament\Pages\ExternalIntegrations::getUrl() }}" target="_blank" rel="noopener noreferrer">{{ $label }} <x-heroicon-m-arrow-top-right-on-square class="h-4 w-4" /></a>
                @else
                    <button type="button" wire:click="selectTab('{{ $key }}')" @class(['is-active' => $activeTab === $key])>{{ $label }}</button>
                @endif
            @endforeach
        </nav>

        <section class="oq-assurance">
            <x-heroicon-o-shield-check class="h-6 w-6 shrink-0" />
            <div>
                <strong>Reservasi belum menjadi nomor antrean</strong>
                <p>Nomor baru diterbitkan setelah pemohon datang dan check-in. Mode Online Penuh hanya menghentikan cetak tiket onsite pada waktu yang ditentukan.</p>
            </div>
        </section>

        @if ($activeTab === 'dashboard')
            <div class="oq-stats">
                <article><span>Reservasi hari ini</span><strong>{{ $dashboardReservationStats['total'] }}</strong><small>Semua layanan online</small></article>
                <article><span>Sudah check-in</span><strong>{{ $dashboardReservationStats['checked_in'] }}</strong><small>Nomor telah diterbitkan</small></article>
                <article><span>Online Penuh mendatang</span><strong>{{ $this->specialWindows->where('mode', 'online_only')->where('is_active', true)->count() }}</strong><small>Onsite dialihkan otomatis</small></article>
            </div>

            <div class="oq-grid-main">
                <section class="oq-panel">
                    <div class="oq-panel-heading">
                        <div><h2>Jadwal layanan online</h2><p>Lima sesi terdekat dari jadwal mingguan yang tersedia.</p></div>
                        <span class="oq-chip oq-chip-success">Terhubung ke backend</span>
                    </div>
                        <div class="oq-table-wrap"><table class="oq-table"><thead><tr><th>Layanan</th><th>Hari & sesi</th><th>Kuota</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                            @forelse($this->sessions->take(5) as $session)
                                <tr><td><strong>{{ $session->service?->name }}</strong><small>{{ $session->service?->instansi?->nama_instansi }}</small></td><td>{{ [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'][$session->day_of_week] ?? '-' }} · {{ substr($session->starts_at,0,5) }}–{{ substr($session->ends_at,0,5) }}</td><td><b>{{ $session->quota }}</b></td><td><span class="oq-chip {{ $session->status === 'active' ? 'oq-chip-success' : 'oq-chip-warning' }}">{{ $session->status === 'active' ? 'Aktif' : ($session->status === 'draft' ? 'Draft' : 'Ditutup') }}</span></td><td><x-filament::button size="sm" color="gray" icon="heroicon-o-pencil-square" wire:click="openEditSession({{ $session->id }})">Edit</x-filament::button></td></tr>
                            @empty<tr><td colspan="5" class="text-center">Belum ada sesi. Tambahkan sesi pertama melalui tombol di atas.</td></tr>@endforelse
                        </tbody></table></div>
                </section>

                <aside class="oq-panel oq-phone-preview">
                    <div class="oq-panel-heading"><div><h2>Pratinjau formulir masyarakat</h2><p>Reservasi antrean online</p></div><span class="oq-chip">Mobile</span></div>
                    <div class="oq-phone">
                        <div class="oq-phone-brand">SIOLA Q · Antrean Online</div>
                        <div class="oq-phone-body">
                            <h3>Ambil antrean online</h3>
                            <p>Pilih layanan dan jadwal kedatangan Anda.</p>
                            <div class="oq-privacy"><x-heroicon-o-lock-closed class="h-5 w-5" /><span>NIK wajib, disimpan aman, dan tidak dicetak pada tiket.</span></div>
                            <label>NIK *<input disabled value="3578••••••••1234"></label>
                            <label>Nama lengkap *<input disabled value="Budi Santoso"></label>
                            <label>Layanan *<select disabled><option>Administrasi Kependudukan</option></select></label>
                            <label>Tanggal dan sesi *<select disabled><option>09 Sep 2026 · 08.00–09.00</option></select></label>
                            <label>Nomor WhatsApp *<input disabled value="0812 3456 7890"></label>
                            <button disabled>Buat reservasi</button>
                        </div>
                    </div>
                </aside>
            </div>

            <section class="oq-flow" aria-label="Alur reservasi hingga nomor antrean layanan">
                @foreach (['Reservasi online', 'Bukti & kode booking', 'Scan QR di mesin', 'Konfirmasi melalui HP', 'Nomor antrean dicetak'] as $index => $step)
                    <div><span>{{ $index + 1 }}</span><strong>{{ $step }}</strong></div>@if (! $loop->last)<x-heroicon-o-chevron-right class="h-5 w-5" />@endif
                @endforeach
            </section>
        @elseif ($activeTab === 'sessions')
            <section class="oq-guide">
                <div><span>1</span><strong>Jadwal mingguan</strong><small>Tentukan layanan, hari, jam, dan kuota online rutin.</small></div>
                <x-heroicon-o-chevron-right class="h-5 w-5" />
                <div><span>2</span><strong>Jadwal khusus</strong><small>Pilih Hybrid atau Online Penuh untuk tanggal tertentu.</small></div>
                <x-heroicon-o-chevron-right class="h-5 w-5" />
                <div><span>3</span><strong>Aktifkan</strong><small>Sistem menjalankan dan memulihkan kanal secara otomatis.</small></div>
            </section>
            <section class="oq-panel">
                <div class="oq-panel-heading"><div><h2>Jadwal mingguan & kuota</h2><p>Jadwal rutin yang ditampilkan pada formulir masyarakat setiap minggu.</p></div><x-filament::button icon="heroicon-o-plus" wire:click="mountAction('createSession')">Tambah jadwal mingguan</x-filament::button></div>
                <div class="oq-session-layout">
                    <div class="oq-table-wrap"><table class="oq-table"><thead><tr><th>Layanan</th><th>Hari</th><th>Jam</th><th>Kuota online</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                        @forelse($this->sessions as $session)<tr><td><strong>{{ $session->service?->name }}</strong><small>{{ $session->service?->instansi?->nama_instansi }}</small></td><td>{{ [1=>'Senin',2=>'Selasa',3=>'Rabu',4=>'Kamis',5=>'Jumat',6=>'Sabtu',7=>'Minggu'][$session->day_of_week] ?? '-' }}</td><td>{{ substr($session->starts_at,0,5) }}–{{ substr($session->ends_at,0,5) }}</td><td>{{ $session->quota }}</td><td><span class="oq-chip {{ $session->status === 'active' ? 'oq-chip-success' : 'oq-chip-warning' }}">{{ ucfirst($session->status) }}</span></td><td><x-filament::button size="sm" color="gray" icon="heroicon-o-pencil-square" wire:click="openEditSession({{ $session->id }})">Edit</x-filament::button></td></tr>@empty<tr><td colspan="6" class="text-center">Belum ada jadwal mingguan. Klik Tambah jadwal mingguan untuk memulai.</td></tr>@endforelse
                    </tbody></table></div>
                    <aside class="oq-help-card"><x-heroicon-o-light-bulb class="h-9 w-9" /><h3>Bedanya apa?</h3><p><b>Jadwal mingguan</b> mengatur slot booking rutin. <b>Jadwal khusus</b> mengatur apakah kiosk onsite tetap boleh mencetak nomor pada tanggal tertentu.</p><span>Kuota tetap dihitung per layanan dan sesi</span></aside>
                </div>
            </section>
            <section class="oq-panel oq-special-panel">
                <div class="oq-panel-heading"><div><h2>Jadwal khusus & pengalihan kanal</h2><p>Gunakan Online Penuh hanya saat layanan diperkirakan membludak. Aturan berakhir otomatis sesuai jam selesai.</p></div><x-filament::button color="warning" icon="heroicon-o-calendar-days" wire:click="mountAction('createSpecialWindow')">Tambah jadwal khusus</x-filament::button></div>
                <div class="oq-mode-explainer"><div><b>Hybrid</b><span>Booking online dan cetak tiket onsite sama-sama berjalan.</span></div><div class="is-online-only"><b>Online Penuh</b><span>Cetak onsite dihentikan sementara; check-in reservasi online tetap berjalan.</span></div></div>
                <div class="oq-table-wrap"><table class="oq-table"><thead><tr><th>Layanan</th><th>Tanggal</th><th>Waktu berlaku</th><th>Mode</th><th>Status</th><th>Catatan</th><th>Aksi</th></tr></thead><tbody>
                    @forelse($this->specialWindows as $window)<tr><td><strong>{{ $window->service?->name }}</strong><small>{{ $window->service?->instansi?->nama_instansi }}</small></td><td>{{ $window->date->translatedFormat('d M Y') }}</td><td>{{ substr($window->starts_at,0,5) }}–{{ substr($window->ends_at,0,5) }}</td><td><span class="oq-chip {{ $window->mode === 'online_only' ? 'oq-chip-online-only' : 'oq-chip-success' }}">{{ $window->mode === 'online_only' ? 'Online Penuh' : 'Hybrid' }}</span></td><td><span class="oq-chip {{ $window->is_active ? 'oq-chip-success' : 'oq-chip-warning' }}">{{ $window->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td>{{ $window->note ?: '—' }}</td><td><x-filament::button size="sm" color="gray" icon="heroicon-o-pencil-square" wire:click="openEditSpecialWindow({{ $window->id }})">Edit</x-filament::button></td></tr>
                    @empty<tr><td colspan="7" class="text-center">Belum ada jadwal khusus. Antrean onsite berjalan normal mengikuti jadwal operasional.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        @elseif ($activeTab === 'participants')
            <livewire:online-queue-participant-table key="online-queue-participant-table" />
        @elseif ($activeTab === 'checkin')
            <div class="oq-grid-main"><section class="oq-panel"><div class="oq-panel-heading"><div><h2>Check-in mandiri melalui QR</h2><p>Pemohon memindai QR berumur 90 detik menggunakan HP.</p></div><span class="oq-chip {{ $onlineSettings->global_enabled && $onlineSettings->regular_enabled ? 'oq-chip-success' : 'oq-chip-warning' }}">{{ $onlineSettings->global_enabled && $onlineSettings->regular_enabled ? 'Siap digunakan' : 'Menunggu aktivasi' }}</span></div><div class="oq-kiosk-preview"><x-heroicon-o-qr-code class="h-20 w-20" /><h3>Layar QR mesin antrean</h3><p>Validasi memakai kode booking dan 4 digit terakhir NIK. Satu reservasi hanya menerbitkan satu nomor.</p><strong>Backend terlindungi</strong><x-filament::button tag="a" :href="route('online-queue.checkin.kiosk')" target="_blank" color="gray">Buka layar kiosk</x-filament::button></div></section><aside class="oq-panel"><h2>Monitoring hari ini</h2><p>Status kedatangan pemohon antrean online.</p><div class="oq-stats" style="grid-template-columns:1fr"><article><span>Belum hadir / check-in</span><strong>{{ $dashboardReservationStats['booked'] }}</strong></article><article><span>Hadir / sudah check-in</span><strong>{{ $dashboardReservationStats['checked_in'] }}</strong></article></div><div class="oq-note">Check-in bantuan petugas tersedia pada tab Pendaftar untuk reservasi yang belum hadir.</div></aside></div>
        @elseif ($activeTab === 'links')
            <div class="oq-link-grid">
                <section class="oq-panel">
                    <x-heroicon-o-link class="h-7 w-7" />
                    <h2>Formulir pendaftaran publik</h2>
                    <p>Form booking dengan NIK wajib untuk mengambil slot layanan.</p>
                    @if ($onlinePublicReady)
                        <div class="oq-link-state is-ready"><x-heroicon-o-check-circle class="h-5 w-5" /><span>Pendaftaran online siap menerima reservasi.</span></div>
                        <code>{{ route('online-queue.index') }}</code>
                        <x-filament::button tag="a" :href="route('online-queue.index')" target="_blank" color="gray">Buka formulir</x-filament::button>
                    @else
                        <div class="oq-link-state is-unavailable"><x-heroicon-o-lock-closed class="h-5 w-5" /><span>{{ $onlineUnavailableMessage }} Aktifkan minimal satu sesi terlebih dahulu.</span></div>
                        <code class="is-disabled">Formulir publik terkunci</code>
                        <x-filament::button disabled color="gray">Formulir belum tersedia</x-filament::button>
                    @endif
                </section>
                <section class="oq-panel">
                    <x-heroicon-o-qr-code class="h-7 w-7" />
                    <h2>Layar check-in kiosk</h2>
                    <p>QR dinamis dipindai pemohon saat tiba di MPP SIOLA.</p>
                    @if ($onlinePublicReady)
                        <div class="oq-link-state is-ready"><x-heroicon-o-check-circle class="h-5 w-5" /><span>QR check-in siap ditampilkan di mesin kiosk.</span></div>
                        <code>{{ route('online-queue.checkin.kiosk') }}</code>
                        <x-filament::button tag="a" :href="route('online-queue.checkin.kiosk')" target="_blank" color="gray">Buka layar kiosk</x-filament::button>
                    @else
                        <div class="oq-link-state is-unavailable"><x-heroicon-o-no-symbol class="h-5 w-5" /><span>Belum ada antrean online aktif. QR check-in tidak ditampilkan.</span></div>
                        <code class="is-disabled">QR check-in belum tersedia</code>
                        <x-filament::button disabled color="gray">Layar kiosk belum tersedia</x-filament::button>
                    @endif
                </section>
            </div>
        @elseif ($activeTab === 'settings')
            @php
                $identityEnabled = (bool) config('citizen_identity.enabled', false);
                $identityDriver = (string) config('citizen_identity.driver', 'disabled');
                $identityAuthType = (string) config('citizen_identity.auth_type', 'bearer');
                $identityCredentialsReady = match ($identityAuthType) {
                    'client_credentials' => filled(config('citizen_identity.client_id')) && filled(config('citizen_identity.client_secret')),
                    'none' => true,
                    default => filled(config('citizen_identity.token')),
                };
                $identityConfigured = $identityDriver !== 'disabled'
                    && filled(config('citizen_identity.endpoint'))
                    && $identityCredentialsReady;
                $identityStatus = $identityEnabled && $identityConfigured
                    ? 'Menunggu uji koneksi'
                    : ($identityConfigured ? 'Konfigurasi server tersedia' : 'Belum dikonfigurasi');
                $failureMode = (string) config('citizen_identity.failure_mode', 'manual_review');
                $identityVerificationSummary = $this->identityVerificationSummary;
                $verificationCount = $identityVerificationSummary['count'];
                $verificationLogs = $identityVerificationSummary['logs'];
            @endphp

            <div class="oq-identity-settings">
                <section @class(['oq-identity-status', 'is-ready' => $identityConfigured, 'is-enabled' => $identityEnabled && $identityConfigured])>
                    <div class="oq-identity-status__icon"><x-heroicon-o-link class="h-6 w-6" /></div>
                    <div>
                        <strong>{{ $identityStatus }}</strong>
                        <p>
                            @if (! $identityConfigured)
                                Endpoint dan kredensial API resmi belum tersedia pada server.
                            @elseif (! $identityEnabled)
                                Konfigurasi tersedia, tetapi verifikasi publik masih dinonaktifkan.
                            @else
                                Aktivasi tersedia, tetapi status terhubung baru dapat dipastikan setelah uji koneksi berhasil.
                            @endif
                        </p>
                    </div>
                    <button type="button" disabled title="Backend uji koneksi belum tersedia">Uji koneksi</button>
                </section>

                <div class="oq-identity-grid">
                    <div class="oq-identity-main">
                        <section class="oq-panel oq-identity-activation">
                            <div>
                                <h2>Verifikasi NIK otomatis</h2>
                                <p>Cocokkan NIK dan nama pemohon saat pendaftaran antrean online.</p>
                            </div>
                            <button class="oq-switch" type="button" role="switch" aria-checked="{{ $identityEnabled ? 'true' : 'false' }}" disabled title="Aktivasi tersedia setelah koneksi API berhasil diuji"><span></span></button>
                        </section>

                        <section class="oq-panel">
                            <div class="oq-panel-heading"><div><h2>Konfigurasi server</h2><p>Kredensial hanya dikelola di server dan tidak pernah dikirim ke browser.</p></div><span class="oq-chip {{ $identityConfigured ? 'oq-chip-success' : 'oq-chip-warning' }}">{{ $identityConfigured ? 'Tersedia' : 'Belum lengkap' }}</span></div>
                            <div class="oq-identity-fields">
                                <div><span>Environment</span><strong>{{ app()->environment('production') ? 'Produksi' : 'Pengujian' }}</strong></div>
                                <div><span>Provider</span><strong>{{ $identityDriver === 'disabled' ? 'Belum dipilih' : strtoupper($identityDriver) }}</strong></div>
                                <div><span>Client ID</span><strong>{{ filled(config('citizen_identity.client_id')) ? 'Tersimpan di server' : ($identityAuthType === 'client_credentials' ? 'Belum tersedia' : 'Tidak digunakan') }}</strong></div>
                                <div><span>Secret / token</span><strong>{{ filled(config('citizen_identity.client_secret')) || filled(config('citizen_identity.token')) ? 'Tersimpan aman di server' : 'Belum tersedia' }}</strong></div>
                            </div>
                        </section>

                        <section class="oq-panel">
                            <h2>Jika API gagal atau lambat</h2>
                            <p>Strategi fallback mencegah pendaftaran berhenti total ketika layanan Dukcapil terganggu.</p>
                            <div class="oq-identity-fallback">
                                <div><x-heroicon-o-shield-check class="h-6 w-6" /><span><strong>{{ $failureMode === 'manual_review' ? 'Antre verifikasi manual admin' : ucfirst(str_replace('_', ' ', $failureMode)) }}</strong><small>Pemohon tidak langsung ditolak dan identitas tidak dianggap terverifikasi.</small></span></div>
                                <span class="oq-chip oq-chip-warning">Rekomendasi aman</span>
                            </div>
                        </section>
                    </div>

                    <aside class="oq-identity-side">
                        <section class="oq-panel">
                            <h2>Pemakaian API bulan ini</h2>
                            <div class="oq-identity-usage"><strong>{{ number_format($verificationCount, 0, ',', '.') }}</strong><span>verifikasi tercatat aplikasi</span></div>
                            <div class="oq-identity-quota"><span>Kuota vendor</span><strong>Belum tersedia</strong></div>
                            <p>Kuota vendor belum tersedia. Angka di atas bukan klaim sisa kuota Dukcapil.</p>
                        </section>

                        <section class="oq-panel">
                            <div class="oq-panel-heading"><div><h2>Log verifikasi terbaru</h2><p>NIK selalu ditampilkan tersamarkan.</p></div></div>
                            <div class="oq-identity-logs">
                                @forelse ($verificationLogs as $log)
                                    <div><span><strong>{{ $log->identity_verified_at?->timezone('Asia/Jakarta')->format('H.i') ?? '—' }} WIB</strong><small>{{ $log->masked_nik }}</small></span><em class="{{ $log->identity_verification_status === 'verified' ? 'is-match' : 'is-review' }}">{{ $log->identity_verification_status === 'verified' ? 'Cocok' : 'Perlu tinjau' }}</em></div>
                                @empty
                                    <div class="is-empty"><span><strong>Belum ada log verifikasi</strong><small>Riwayat akan tampil setelah API resmi diaktifkan.</small></span></div>
                                @endforelse
                            </div>
                        </section>
                    </aside>
                </div>

                <section class="oq-identity-security">
                    <x-heroicon-o-shield-check class="h-6 w-6" />
                    <div><strong>Perlindungan data kependudukan</strong><p>NIK disimpan terenkripsi dan tidak dalam bentuk teks biasa. Hash khusus digunakan untuk mencegah booking ganda; secret API tetap berada di server.</p></div>
                </section>
            </div>
        @else
            <section class="oq-panel oq-settings-placeholder"><x-heroicon-o-cog-6-tooth class="h-12 w-12" /><h2>Bagian belum tersedia</h2><p>Fungsi ini sedang disiapkan.</p><span>Dalam tahap pengembangan</span></section>
        @endif
    </div>

    @push('styles')
        <style>
            .oq-page{display:grid;gap:1.15rem;min-width:0}.oq-development,.oq-assurance{display:flex;gap:.8rem;align-items:flex-start;border-radius:1rem;padding:1rem}.oq-development{background:#fff8e8;border:1px solid #f6d98b;color:#8a5600}.dark .oq-development{background:#402f12;border-color:#72551d;color:#ffe09a}.oq-development strong,.oq-assurance strong{font-weight:800}.oq-development p,.oq-assurance p{margin:.15rem 0 0;font-size:.83rem}.oq-header,.oq-toolbar,.oq-panel-heading{display:flex;align-items:center;justify-content:space-between;gap:1rem}.oq-header h1{font-size:1.7rem;font-weight:800;margin:0}.oq-header p,.oq-panel p{margin:.3rem 0;color:#667085;font-size:.86rem}.dark .oq-header p,.dark .oq-panel p{color:#9ca3af}.oq-global-status{display:inline-flex;align-items:center;gap:.45rem;font-size:.8rem;color:#a16207}.oq-global-status i{width:.55rem;height:.55rem;border-radius:999px;background:#f59e0b}.oq-mode{display:flex;gap:.25rem;padding:.25rem;border-radius:.8rem;background:#eef2f7}.dark .oq-mode{background:#1f2937}.oq-mode button{display:flex;gap:.45rem;align-items:center;border:0;border-radius:.6rem;background:transparent;color:#667085;padding:.65rem .9rem;font-size:.86rem;font-weight:700}.oq-mode button.is-active{background:white;color:#1d5fbf;box-shadow:0 1px 4px #1725541a}.dark .oq-mode button.is-active{background:#374151;color:#bfdbfe}.oq-tabs{display:flex;gap:.25rem;overflow:auto;border-bottom:1px solid #dce4ef}.dark .oq-tabs{border-color:#374151}.oq-tabs button,.oq-tabs a{display:flex;flex:none;align-items:center;gap:.3rem;border:0;border-bottom:3px solid transparent;background:transparent;color:#667085;padding:.75rem .9rem;font-size:.84rem;font-weight:700;text-decoration:none}.oq-tabs button.is-active{color:#2563eb;border-color:#2563eb}.oq-tabs a:hover{color:#2563eb}.oq-assurance{background:#edf6ff;border:1px solid #bddbff;color:#174d9b}.dark .oq-assurance{background:#152d4d;border-color:#255487;color:#dbeafe}.oq-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:1rem}.oq-stats article,.oq-panel{background:white;border:1px solid #dfe7f1;border-radius:1rem;padding:1.1rem;min-width:0}.dark .oq-stats article,.dark .oq-panel{background:#111827;border-color:#374151}.oq-stats span,.oq-stats small{display:block;color:#667085;font-size:.78rem}.dark .oq-stats span,.dark .oq-stats small{color:#9ca3af}.oq-stats strong{display:block;font-size:1.85rem;color:#174d9b;margin:.15rem 0}.dark .oq-stats strong{color:#93c5fd}.oq-grid-main{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(300px,.75fr);gap:1rem}.oq-panel h2{font-weight:800;margin:0;font-size:1.05rem}.oq-panel h3{font-weight:800;margin:.7rem 0 .25rem}.oq-chip{display:inline-flex;border-radius:999px;padding:.26rem .55rem;font-size:.7rem;font-weight:800;background:#eaf0f8;color:#49617f}.oq-chip-success{background:#daf6e4;color:#14733d}.oq-chip-warning{background:#fff0cf;color:#966300}.dark .oq-chip{background:#374151;color:#d1d5db}.dark .oq-chip-success{background:#17452b;color:#a7f3d0}.dark .oq-chip-warning{background:#4b3814;color:#fde68a}.oq-table-wrap{overflow:auto;margin-top:.8rem}.oq-table{width:100%;border-collapse:collapse;font-size:.83rem}.oq-table th,.oq-table td{text-align:left;padding:.8rem;border-bottom:1px solid #e8edf4;vertical-align:middle}.dark .oq-table th,.dark .oq-table td{border-color:#303b4d}.oq-table th{font-size:.7rem;text-transform:uppercase;color:#667085}.oq-table td strong,.oq-table td small{display:block}.oq-table td small{color:#7b8799;margin-top:.15rem}.oq-progress{height:.3rem;border-radius:999px;background:#e6ebf2;margin-top:.35rem;min-width:90px;overflow:hidden}.oq-progress i{display:block;height:100%;background:#2878e3}.oq-phone-preview{background:#eef6ff}.dark .oq-phone-preview{background:#15253c}.oq-phone{max-width:350px;margin:.8rem auto 0;border-radius:1.1rem;background:white;border:1px solid #cbd8e9;overflow:hidden}.dark .oq-phone{background:#111827;border-color:#475569}.oq-phone-brand{padding:.8rem 1rem;border-bottom:1px solid #e5ebf3;color:#175aaa;font-size:.78rem;font-weight:800}.dark .oq-phone-brand{border-color:#374151;color:#93c5fd}.oq-phone-body{padding:1rem}.oq-phone-body h3{font-size:1.1rem}.oq-phone-body label,.oq-field{display:block;font-size:.72rem;font-weight:700;margin-top:.65rem}.oq-phone-body input,.oq-phone-body select,.oq-field input{display:block;width:100%;margin-top:.3rem;border:1px solid #ccd6e3;border-radius:.55rem;padding:.55rem;background:white;color:#344054}.dark .oq-phone-body input,.dark .oq-phone-body select,.dark .oq-field input{background:#1f2937;border-color:#475569;color:#d1d5db}.oq-phone-body button{width:100%;border:0;border-radius:.55rem;background:#1f2937;color:white;padding:.65rem;margin-top:.8rem;font-weight:800}.oq-privacy{display:flex;gap:.5rem;padding:.65rem;border-radius:.65rem;background:#eaf8ef;color:#16713d;font-size:.72rem;margin-top:.7rem}.dark .oq-privacy{background:#173c29;color:#a7f3d0}.oq-flow{display:flex;align-items:center;justify-content:center;gap:.6rem;flex-wrap:wrap;padding:.9rem}.oq-flow>div{display:flex;gap:.5rem;align-items:center;font-size:.78rem}.oq-flow>div span{display:grid;place-items:center;width:1.7rem;height:1.7rem;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-weight:800}.oq-event-grid,.oq-link-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.8rem;margin-top:1rem}.oq-event-grid article{border:1px solid #e2e8f0;border-radius:.8rem;padding:1rem}.dark .oq-event-grid article{border-color:#374151}.oq-session-layout{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(240px,.5fr);gap:1rem}.oq-dev-box,.oq-settings-placeholder,.oq-kiosk-preview{text-align:center;display:grid;place-items:center;align-content:center;gap:.5rem;min-height:220px;padding:1.3rem;border:1px dashed #bdcadb;border-radius:.8rem;color:#667085}.dark .oq-dev-box,.dark .oq-settings-placeholder,.dark .oq-kiosk-preview{border-color:#4b5563;color:#9ca3af}.oq-dev-box span,.oq-settings-placeholder span,.oq-kiosk-preview strong{display:inline-flex;border-radius:999px;padding:.35rem .65rem;background:#fff0cf;color:#966300;font-size:.72rem;font-weight:800}.oq-note{margin-top:1rem;padding:.8rem;background:#fff8e8;color:#8a5600;border-radius:.7rem;font-size:.75rem}.oq-link-grid code{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding:.7rem;margin:.8rem 0;border-radius:.6rem;background:#f3f6fa;font-size:.72rem}.oq-link-grid code.is-disabled{color:#98a2b3;background:#f7f8fa}.oq-link-state{display:flex;align-items:flex-start;gap:.55rem;margin-top:1rem;padding:.75rem;border-radius:.7rem;font-size:.78rem;font-weight:700;line-height:1.4}.oq-link-state svg{flex:none}.oq-link-state.is-ready{background:#eaf8ef;color:#16713d}.oq-link-state.is-unavailable{background:#fff4df;color:#9a5b00;border:1px solid #f5d294}.oq-link-grid .fi-btn[disabled]{opacity:.55;cursor:not-allowed}.dark .oq-link-grid code{background:#1f2937}.dark .oq-link-grid code.is-disabled{background:#273142;color:#9ca3af}.dark .oq-link-state.is-ready{background:#173c29;color:#a7f3d0}.dark .oq-link-state.is-unavailable{background:#4b3814;color:#fde68a;border-color:#72551d}.oq-settings-placeholder{min-height:340px}.oq-kiosk-preview svg{color:#2563eb}
            .oq-global-status.is-enabled{color:#15803d}.oq-global-status.is-enabled i{background:#22c55e}.oq-section-title{display:flex;align-items:center;gap:.7rem}.oq-section-title>svg{color:#2563eb}.oq-section-title strong,.oq-section-title span{display:block}.oq-section-title span{margin-top:.12rem;color:#667085;font-size:.75rem}.oq-toolbar-actions{display:flex;gap:.55rem;flex-wrap:wrap}.oq-guide{display:flex;align-items:center;justify-content:center;gap:.8rem;padding:1rem;border:1px solid #d9e7f8;border-radius:1rem;background:#f4f8ff}.oq-guide>div{display:grid;grid-template-columns:auto 1fr;column-gap:.55rem;align-items:center;max-width:260px}.oq-guide>div>span{grid-row:1/3;display:grid;place-items:center;width:2rem;height:2rem;border-radius:999px;background:#2563eb;color:#fff;font-weight:800}.oq-guide strong,.oq-guide small{display:block}.oq-guide small{color:#667085;font-size:.7rem}.oq-help-card{padding:1.1rem;border:1px solid #cfe0f5;border-radius:.8rem;background:#f5f9ff;color:#24466f}.oq-help-card h3{margin:.65rem 0 .25rem;font-weight:800}.oq-help-card p{font-size:.78rem}.oq-help-card span{display:block;margin-top:.75rem;padding:.55rem;border-radius:.55rem;background:#e4effd;font-size:.7rem;font-weight:700}.oq-special-panel{border-color:#f1d08c}.oq-mode-explainer{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem;margin-top:1rem}.oq-mode-explainer>div{padding:.8rem;border-radius:.7rem;background:#edf8f1;color:#166534}.oq-mode-explainer>div.is-online-only{background:#fff5df;color:#92400e}.oq-mode-explainer b,.oq-mode-explainer span{display:block}.oq-mode-explainer span{margin-top:.15rem;font-size:.72rem}.oq-chip-online-only{background:#ffedd5;color:#9a3412}
            .oq-identity-settings{display:grid;gap:1rem}.oq-identity-status{display:grid;grid-template-columns:auto minmax(0,1fr) auto;align-items:center;gap:.85rem;padding:1rem 1.1rem;border:1px solid #f2ce7b;border-radius:1rem;background:#fff9e9;color:#754b00}.oq-identity-status.is-ready{border-color:#b9d8f7;background:#eef6ff;color:#174d9b}.oq-identity-status.is-enabled{border-color:#a7ddb9;background:#eaf8ef;color:#14733d}.oq-identity-status__icon{display:grid;place-items:center;width:2.5rem;height:2.5rem;border-radius:.75rem;background:#ffffffa6}.oq-identity-status strong{font-weight:850}.oq-identity-status p{margin:.15rem 0 0;font-size:.78rem}.oq-identity-status>button{min-height:2.35rem;padding:0 .9rem;border:1px solid currentColor;border-radius:.6rem;background:#ffffff8c;color:inherit;font-size:.78rem;font-weight:800;opacity:.55;cursor:not-allowed}.oq-identity-grid{display:grid;grid-template-columns:minmax(0,1.45fr) minmax(280px,.65fr);gap:1rem;align-items:start}.oq-identity-main,.oq-identity-side{display:grid;gap:1rem}.oq-identity-activation{display:flex;align-items:center;justify-content:space-between;gap:1rem}.oq-switch{position:relative;width:3rem;height:1.7rem;flex:0 0 auto;padding:0;border:0;border-radius:99px;background:#cbd5e1;opacity:.8;cursor:not-allowed}.oq-switch span{position:absolute;top:.2rem;left:.2rem;width:1.3rem;height:1.3rem;border-radius:50%;background:#fff;box-shadow:0 1px 4px #0f172a33}.oq-switch[aria-checked=true]{background:#16a34a}.oq-switch[aria-checked=true] span{left:1.5rem}.oq-identity-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:.7rem;margin-top:1rem}.oq-identity-fields>div{display:grid;gap:.25rem;padding:.8rem;border:1px solid #e1e8f0;border-radius:.7rem;background:#f8fafc}.dark .oq-identity-fields>div{border-color:#374151;background:#1f2937}.oq-identity-fields span{color:#667085;font-size:.7rem;font-weight:700}.oq-identity-fields strong{overflow:hidden;color:#25364d;font-size:.84rem;text-overflow:ellipsis}.dark .oq-identity-fields strong{color:#e5e7eb}.oq-identity-fallback{display:flex;align-items:center;justify-content:space-between;gap:.8rem;margin-top:1rem;padding:.85rem;border:1px solid #bfdbfe;border-radius:.75rem;background:#eff6ff}.dark .oq-identity-fallback{border-color:#31557e;background:#172c46}.oq-identity-fallback>div{display:flex;align-items:center;gap:.65rem;color:#174d9b}.dark .oq-identity-fallback>div{color:#bfdbfe}.oq-identity-fallback span strong,.oq-identity-fallback span small{display:block}.oq-identity-fallback span small{margin-top:.12rem;color:#667085;font-size:.7rem}.dark .oq-identity-fallback span small{color:#9ca3af}.oq-identity-usage{display:flex;align-items:baseline;gap:.5rem;margin-top:1rem}.oq-identity-usage strong{color:#174d9b;font-size:2rem}.dark .oq-identity-usage strong{color:#93c5fd}.oq-identity-usage span{color:#667085;font-size:.72rem}.oq-identity-quota{display:flex;justify-content:space-between;gap:1rem;margin:.75rem 0;padding:.65rem .75rem;border-radius:.6rem;background:#f3f6fa;font-size:.72rem}.dark .oq-identity-quota{background:#1f2937}.oq-identity-quota span{color:#667085}.oq-identity-logs{display:grid;margin-top:.7rem;border:1px solid #e3e9f1;border-radius:.7rem;overflow:hidden}.dark .oq-identity-logs{border-color:#374151}.oq-identity-logs>div{display:flex;align-items:center;justify-content:space-between;gap:.7rem;padding:.7rem .75rem;border-bottom:1px solid #e8edf4}.dark .oq-identity-logs>div{border-color:#374151}.oq-identity-logs>div:last-child{border-bottom:0}.oq-identity-logs span strong,.oq-identity-logs span small{display:block}.oq-identity-logs span strong{font-size:.76rem}.oq-identity-logs span small{margin-top:.12rem;color:#667085;font-size:.68rem}.oq-identity-logs em{padding:.25rem .48rem;border-radius:99px;font-size:.65rem;font-style:normal;font-weight:800}.oq-identity-logs em.is-match{background:#dcfce7;color:#166534}.oq-identity-logs em.is-review{background:#fef3c7;color:#92400e}.oq-identity-logs .is-empty{justify-content:center;min-height:5.5rem;text-align:center}.oq-identity-security{display:flex;align-items:flex-start;gap:.75rem;padding:1rem;border:1px solid #bddbff;border-radius:1rem;background:#edf6ff;color:#174d9b}.dark .oq-identity-security{border-color:#255487;background:#152d4d;color:#dbeafe}.oq-identity-security svg{flex:0 0 auto}.oq-identity-security strong{font-weight:800}.oq-identity-security p{margin:.2rem 0 0;font-size:.78rem}
            .oq-page .fi-fo-date-time-picker-panel [role="option"].bg-gray-50{background:#2563eb!important;color:#fff!important;font-weight:800;box-shadow:0 0 0 2px #bfdbfe,0 2px 5px #1d4ed833}.dark .oq-page .fi-fo-date-time-picker-panel [role="option"].bg-gray-50{background:#3b82f6!important;color:#fff!important;box-shadow:0 0 0 2px #1e3a8a,0 2px 5px #0006}.oq-filament-table{margin-top:1rem}
            .oq-filters{display:grid;grid-template-columns:160px 180px minmax(220px,1fr) minmax(220px,1fr);gap:.65rem;margin-top:1rem}.oq-filters input,.oq-filters select{width:100%;border:1px solid #cbd5e1;border-radius:.6rem;padding:.62rem .7rem;background:#fff;color:#334155;font-size:.8rem}.dark .oq-filters input,.dark .oq-filters select{background:#1f2937;border-color:#475569;color:#e5e7eb}.oq-row-actions{display:flex;gap:.35rem;flex-wrap:wrap}.oq-row-actions button{border:1px solid #cbd5e1;border-radius:.45rem;background:#fff;color:#334155;padding:.35rem .55rem;font-size:.72rem;font-weight:750}.oq-row-actions button.success{border-color:#86d5a4;color:#14733d}.oq-row-actions button.danger{border-color:#f1aaa5;color:#b42318}.dark .oq-row-actions button{background:#1f2937;color:#e5e7eb}@media(max-width:1100px){.oq-filters{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.oq-filters{grid-template-columns:1fr}}
            @media(max-width:1100px){.oq-grid-main,.oq-session-layout,.oq-identity-grid{grid-template-columns:1fr}.oq-phone{max-width:420px}}@media(max-width:760px){.oq-header,.oq-toolbar,.oq-panel-heading{align-items:flex-start;flex-direction:column}.oq-stats,.oq-link-grid,.oq-event-grid,.oq-identity-fields,.oq-mode-explainer{grid-template-columns:1fr}.oq-flow>svg,.oq-guide>svg{transform:rotate(90deg)}.oq-guide{align-items:stretch;flex-direction:column}.oq-identity-status{grid-template-columns:auto minmax(0,1fr)}.oq-identity-status>button{grid-column:1/-1;width:100%}.oq-identity-activation{align-items:flex-start}}
        </style>
    @endpush
</x-filament-panels::page>
