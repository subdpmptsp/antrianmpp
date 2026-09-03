<x-filament-panels::page>
    @php
        $event = $this->selectedEvent;
        $hasEvent = $event !== null;
        $tabLabels = [
            'events' => 'Daftar event', 'dashboard' => 'Dashboard', 'participants' => 'Peserta',
            'checkin' => 'Check-in', 'links' => 'Tautan & QR', 'settings' => 'Pengaturan',
        ];
        $statusLabels = ['draft' => 'Draft', 'active' => 'Aktif', 'closed' => 'Pendaftaran ditutup', 'completed' => 'Selesai'];
    @endphp

    <div class="event-queue-page">
        <div class="event-queue-page__intro">
            <div>
                <h1>Antrean online / event</h1>
                <p>Satu ruang kerja untuk event, dari pendaftaran publik hingga check-in. Data ini terpisah dari antrean reguler.</p>
            </div>
            <x-filament::button icon="heroicon-o-plus" wire:click="mountAction('createEvent')">Buat event baru</x-filament::button>
        </div>

        @if ($hasEvent)
            <div class="event-queue-page__context">
                <div class="event-queue-page__context-icon">📅</div>
                <div><span>Sedang dikelola</span><strong>{{ $event->name }}</strong>
                    <small>{{ $event->starts_at?->translatedFormat('d M Y') ?? 'Tanggal belum diatur' }} · {{ $statusLabels[$event->status] ?? $event->status }}</small>
                </div>
                <select wire:change="selectEvent($event.target.value)" aria-label="Pindah event">
                    @foreach ($this->events as $switchEvent)<option value="{{ $switchEvent->id }}" @selected($switchEvent->id === $event->id)>{{ $switchEvent->name }}</option>@endforeach
                </select>
            </div>
        @endif

        <div class="event-queue-page__tabs" role="tablist">
            @foreach ($tabLabels as $key => $label)
                <button type="button" wire:click="selectTab('{{ $key }}')" @class(['is-active' => $this->activeTab === $key, 'is-disabled' => $key !== 'events' && ! $hasEvent])>{{ $label }}</button>
            @endforeach
        </div>

        @if ($this->activeTab === 'events')
            <div class="event-queue-page__events">
                @forelse ($this->events as $item)
                    <button type="button" wire:click="selectEvent({{ $item->id }})" class="event-card @if($event?->id === $item->id) is-selected @endif">
                        <div class="event-card__top"><span class="event-status event-status--{{ $item->status }}">{{ $statusLabels[$item->status] ?? $item->status }}</span><span>{{ $item->starts_at?->translatedFormat('d M Y') ?? 'Tanggal belum diatur' }}</span></div>
                        <strong>{{ $item->name }}</strong><p>{{ $item->description ?: 'Belum ada deskripsi event.' }}</p>
                        <div class="event-card__metrics"><span>{{ $item->participants_count }} / {{ $item->daily_quota }} pendaftar</span><span>{{ $item->checked_in_count }} check-in</span></div>
                    </button>
                @empty
                    <div class="event-empty"><strong>Belum ada event</strong><span>Buat event baru untuk mulai menyiapkan antrean online.</span></div>
                @endforelse
            </div>
        @elseif (! $hasEvent)
            <div class="event-empty"><strong>Pilih event terlebih dahulu</strong><span>Tab ini membutuhkan konteks event yang jelas.</span></div>
        @elseif ($this->activeTab === 'dashboard')
            @php($registered = $event->participants()->where('status', '!=', 'canceled')->count())
            @php($checkedIn = $event->participants()->whereIn('status', ['checked_in', 'serving'])->count())
            <div class="event-dashboard">
                <div class="event-stat"><span>Total pendaftar</span><strong>{{ $registered }}</strong><small>dari {{ $event->daily_quota }} kuota</small></div>
                <div class="event-stat"><span>Sudah check-in</span><strong>{{ $checkedIn }}</strong><small>peserta hadir</small></div>
                <div class="event-stat"><span>Belum check-in</span><strong>{{ max($registered - $checkedIn, 0) }}</strong><small>menunggu kehadiran</small></div>
                <div class="event-stat"><span>Status event</span><strong class="event-stat__status">{{ $statusLabels[$event->status] }}</strong><small>{{ $event->public_link_enabled ? 'Link publik aktif' : 'Link publik nonaktif' }}</small></div>
            </div>
            <section class="event-panel"><h2>Ringkasan event</h2><p>Event Queue berdiri sendiri. Angka pada halaman ini tidak mengubah data mesin kiosk, loket petugas, TV zona, atau laporan antrean reguler.</p></section>
        @elseif ($this->activeTab === 'participants' || $this->activeTab === 'checkin')
            <section class="event-panel">
                <div class="event-panel__heading"><div><h2>{{ $this->activeTab === 'checkin' ? 'Check-in peserta' : 'Peserta event' }}</h2><p>{{ $this->activeTab === 'checkin' ? 'Tekan check-in setelah QR tiket peserta diverifikasi.' : 'Daftar pendaftar event aktif ini.' }}</p></div>@if($this->activeTab === 'participants')<x-filament::button color="success" icon="heroicon-o-arrow-down-tray" tag="a" :href="route('export.event-participants', ['event' => $event->id, 'status' => $this->participantStatusFilter])">Export Excel Peserta</x-filament::button>@endif</div>
                @if($this->activeTab === 'participants')<div class="event-participant-filter"><label for="participant-status">Status peserta</label><select id="participant-status" wire:model.live="participantStatusFilter"><option value="all">Semua status</option><option value="registered">Terdaftar</option><option value="checked_in">Hadir</option><option value="serving">Dilayani</option><option value="canceled">Batal</option></select></div>@endif
                @if ($this->activeTab === 'checkin')
                    <form class="event-checkin-form" wire:submit="checkInByCode"><input wire:model="checkinCode" autofocus placeholder="Scan QR tiket atau tempel tautan tiket di sini"><x-filament::button type="submit" icon="heroicon-o-qr-code">Check-in QR</x-filament::button></form>
                @endif
                <div class="event-table-wrap"><table class="event-table"><thead><tr><th>Tiket</th><th>Peserta</th><th>WhatsApp</th><th>Status</th>@if($this->activeTab === 'checkin')<th></th>@endif</tr></thead><tbody>
                    @forelse ($this->participants as $participant)<tr><td><strong>{{ $participant->ticket_number }}</strong></td><td>{{ $participant->name }}</td><td>{{ $participant->phone }}</td><td><span class="event-status event-status--{{ $participant->status }}">{{ $participant->status === 'checked_in' ? 'Hadir' : ($participant->status === 'serving' ? 'Dilayani' : ($participant->status === 'registered' ? 'Terdaftar' : 'Batal')) }}</span></td>@if($this->activeTab === 'checkin')<td>@if($participant->status === 'registered')<x-filament::button size="sm" wire:click="checkIn({{ $participant->id }})">Check-in</x-filament::button>@elseif($participant->status === 'checked_in')<x-filament::button size="sm" color="info" wire:click="markAsServing({{ $participant->id }})">Mulai layani</x-filament::button>@endif</td>@endif</tr>
                    @empty<tr><td colspan="5" class="event-table__empty">Belum ada peserta.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        @elseif ($this->activeTab === 'links')
            @php($publicUrl = route('event.registration', $event->public_token))
            @php($tvUrl = route('event.tv', $event->tv_token))
            <div class="event-links">
                <section class="event-panel event-link-card"><div><h2>Link pendaftaran publik</h2><p>Dibagikan kepada masyarakat untuk mengambil tiket event. Validasi NIK/HP dan kuota dilakukan oleh server.</p><code>{{ $publicUrl }}</code><div class="event-link-card__actions"><x-filament::button color="gray" tag="a" :href="$publicUrl" target="_blank">Pratinjau</x-filament::button><x-filament::button color="danger" wire:click="regeneratePublicLink" wire:confirm="Link lama tidak dapat dipakai setelah dibuat ulang. Lanjutkan?">Regenerasi link & QR</x-filament::button></div></div><div class="event-qr">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(150)->generate($publicUrl) !!}</div><div class="event-toggle"><span>Link aktif menerima pendaftaran</span><button wire:click="togglePublicLink" class="toggle @if($event->public_link_enabled) is-on @endif" aria-label="Ubah status link publik"><i></i></button></div></section>
                <section class="event-panel event-link-card"><div><h2>Link tampilan TV ruang tunggu</h2><p>Halaman khusus event tanpa menu admin. Nama peserta disamarkan secara default untuk menjaga privasi.</p><code>{{ $tvUrl }}</code><div class="event-link-card__actions"><x-filament::button color="gray" tag="a" :href="$tvUrl" target="_blank">Pratinjau TV</x-filament::button><x-filament::button color="danger" wire:click="regenerateTvLink" wire:confirm="Link TV lama tidak dapat dipakai setelah dibuat ulang. Lanjutkan?">Regenerasi link & QR</x-filament::button></div></div><div class="event-qr">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(150)->generate($tvUrl) !!}</div><div class="event-toggle"><span>Samarkan nama peserta di TV</span><button wire:click="toggleNameMask" class="toggle @if($event->mask_participant_names) is-on @endif" aria-label="Ubah privasi nama TV"><i></i></button></div></section>
            </div>
        @elseif ($this->activeTab === 'settings')
            <section class="event-panel"><div class="event-panel__heading"><div><h2>Pengaturan event</h2><p>Ubah kuota, jadwal, batas check-in, dan status tanpa meninggalkan halaman.</p></div><x-filament::button icon="heroicon-o-cog-6-tooth" wire:click="mountAction('editEvent')">Ubah pengaturan</x-filament::button></div><dl class="event-settings"><div><dt>Kuota total</dt><dd>{{ $event->daily_quota }} peserta</dd></div><div><dt>Kuota per sesi</dt><dd>{{ $event->session_quota ?: 'Tidak dibatasi' }}</dd></div><div><dt>Batas check-in</dt><dd>{{ $event->checkin_grace_minutes }} menit</dd></div><div><dt>Jadwal</dt><dd>{{ $event->starts_at?->translatedFormat('d M Y H:i') ?? '-' }} WIB</dd></div></dl></section>
        @endif
    </div>

    @push('styles')<style>
        .event-queue-page{display:grid;gap:1.25rem;min-width:0}.event-queue-page__intro,.event-queue-page__context,.event-panel__heading,.event-link-card{display:flex;gap:1rem;justify-content:space-between;align-items:center}.event-queue-page h1{font-size:1.7rem;font-weight:800;margin:0}.event-queue-page h2{font-size:1.1rem;font-weight:800;margin:0}.event-queue-page p{color:#667085;font-size:.875rem;margin:.3rem 0}.event-queue-page__context{padding:1rem;border:1px solid #dbe6f5;border-radius:1rem;background:#f8fbff;justify-content:flex-start}.event-queue-page__context-icon{font-size:1.5rem}.event-queue-page__context span,.event-queue-page__context strong,.event-queue-page__context small{display:block}.event-queue-page__context span,.event-queue-page__context small{font-size:.75rem;color:#667085}.event-queue-page__context select{margin-left:auto;border:1px solid #cfdced;border-radius:.65rem;padding:.65rem;max-width:40%}.event-queue-page__tabs{display:flex;gap:.35rem;border-bottom:1px solid #dbe4ef}.event-queue-page__tabs button{border:0;background:transparent;padding:.7rem .9rem;font-weight:700;font-size:.86rem;border-bottom:3px solid transparent;color:#667085}.event-queue-page__tabs button.is-active{color:#fff;background:#2878e3;border-radius:.55rem .55rem 0 0}.event-queue-page__tabs button.is-disabled{opacity:.42}.event-queue-page__events{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:1rem}.event-card{text-align:left;background:white;border:1px solid #dce5f0;border-radius:1rem;padding:1.1rem;color:#182230;transition:.15s}.event-card:hover,.event-card.is-selected{border-color:#4389ed;box-shadow:0 8px 24px #2465b414}.event-card__top,.event-card__metrics{display:flex;justify-content:space-between;gap:.5rem;font-size:.75rem;color:#72809a}.event-card strong{display:block;font-size:1rem;margin-top:1rem}.event-card p{min-height:2.6em}.event-card__metrics{padding-top:.8rem;border-top:1px solid #edf1f5}.event-status{display:inline-block;border-radius:999px;padding:.25rem .55rem;font-weight:700;font-size:.7rem;background:#eaf0f8;color:#49617f}.event-status--active,.event-status--checked_in{background:#dbf7e5;color:#14733d}.event-status--serving{background:#dcecff;color:#205cab}.event-status--draft,.event-status--registered{background:#fff0cf;color:#966300}.event-status--closed,.event-status--canceled{background:#ffe3e3;color:#a53030}.event-empty,.event-panel{background:white;border:1px solid #dce5f0;border-radius:1rem;padding:1.2rem;min-width:0}.event-empty{display:grid;gap:.3rem;place-items:center;min-height:180px;color:#667085}.event-dashboard{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem}.event-stat{background:white;border:1px solid #dce5f0;border-radius:1rem;padding:1.1rem}.event-stat span,.event-stat small{display:block;color:#6b7b94;font-size:.78rem}.event-stat strong{display:block;font-size:2rem;margin:.25rem 0;color:#174d9b}.event-stat strong.event-stat__status{font-size:1.2rem}.event-checkin-form{display:flex;gap:.6rem;margin-top:1rem}.event-checkin-form input{flex:1;border:1px solid #cfdced;border-radius:.6rem;padding:.65rem}.event-participant-filter{display:flex;align-items:center;gap:.6rem;margin-top:1rem;font-size:.84rem;font-weight:700}.event-participant-filter select{border:1px solid #cfdced;border-radius:.55rem;padding:.55rem;font:inherit}.event-table-wrap{overflow:auto;margin-top:1rem}.event-table{width:100%;border-collapse:collapse;font-size:.86rem}.event-table th,.event-table td{text-align:left;padding:.8rem;border-bottom:1px solid #e6edf5}.event-table th{font-size:.72rem;color:#60708a;text-transform:uppercase}.event-table__empty{text-align:center;color:#8290a7}.event-links{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}.event-link-card{align-items:flex-start;display:grid;grid-template-columns:minmax(0,1fr) auto;position:relative}.event-link-card code{display:block;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;border:1px solid #dce5f0;padding:.65rem;border-radius:.55rem;font-size:.72rem;min-width:0}.event-qr{padding:.55rem;border:1px solid #dce5f0;border-radius:.75rem}.event-link-card__actions{display:flex;gap:.5rem;margin-top:.75rem;flex-wrap:wrap}.event-toggle{grid-column:1/-1;border-top:1px solid #e6edf5;padding-top:.9rem;display:flex;justify-content:space-between;align-items:center;font-size:.85rem}.toggle{width:42px;height:24px;border:0;border-radius:999px;background:#c6d1df;padding:3px}.toggle i{display:block;width:18px;height:18px;border-radius:50%;background:white;transition:.15s}.toggle.is-on{background:#16a34a}.toggle.is-on i{transform:translateX(18px)}.event-settings{display:grid;grid-template-columns:repeat(2,1fr);gap:0;margin-top:1rem}.event-settings div{padding:1rem;border-top:1px solid #e6edf5}.event-settings dt{font-size:.78rem;color:#72809a}.event-settings dd{margin:.25rem 0;font-weight:700}@media(max-width:1300px){.event-links{grid-template-columns:minmax(0,1fr)}}@media(max-width:800px){.event-dashboard{grid-template-columns:1fr}.event-queue-page__intro,.event-queue-page__context{align-items:flex-start;flex-direction:column}.event-queue-page__context select{margin-left:0;max-width:100%;width:100%}.event-queue-page__tabs{overflow:auto}.event-queue-page__tabs button{white-space:nowrap}.event-link-card{grid-template-columns:1fr}.event-qr{display:none}.event-settings{grid-template-columns:1fr}.event-checkin-form{flex-direction:column}}</style>@endpush
</x-filament-panels::page>
