<section class="oq-panel">
    <div class="oq-panel-heading">
        <div>
            <h2>Daftar pendaftar layanan harian</h2>
            <p>NIK selalu ditampilkan tersamarkan dan tindakan admin dicatat.</p>
        </div>
        <x-filament::button
            tag="a"
            :href="route('export.online-queue-reservations', ['date' => $this->reservationDateFilter(), 'status' => $reservationStatus, 'service_id' => $reservationServiceId, 'search' => $reservationSearch])"
            :spa-mode="false"
            color="success"
            icon="heroicon-o-arrow-down-tray"
            download
        >Export Excel</x-filament::button>
    </div>

    <div class="oq-filters">
        <div class="min-w-0">
            <livewire:modern-picker type="date" wire:model.live="reservationDate" key="online-reservation-filter-date" />
        </div>
        <select wire:model.live="reservationStatus" aria-label="Status">
            <option value="all">Semua status</option>
            <option value="booked">Belum hadir / check-in</option>
            <option value="checked_in">Hadir / sudah check-in</option>
            <option value="canceled">Dibatalkan</option>
            <option value="expired">Tidak hadir (no-show)</option>
        </select>
        <select wire:model.live="reservationServiceId" aria-label="Layanan">
            <option value="">Semua layanan</option>
            @foreach ($this->serviceOptions as $serviceId => $serviceLabel)
                <option value="{{ $serviceId }}">{{ $serviceLabel }}</option>
            @endforeach
        </select>
        <input type="search" wire:model.live.debounce.400ms="reservationSearch" placeholder="Cari kode, nama, atau WhatsApp">
    </div>

    <div class="oq-filament-table">{{ $this->table }}</div>
</section>
