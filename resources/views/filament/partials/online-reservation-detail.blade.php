<div class="space-y-4 text-sm">
    <div class="grid grid-cols-2 gap-3">
        <div><span class="text-gray-500">Kode booking</span><strong class="block">{{ $reservation->booking_code }}</strong></div>
        <div><span class="text-gray-500">Status</span><strong class="block">{{ ucfirst(str_replace('_', ' ', $reservation->status)) }}</strong></div>
        <div><span class="text-gray-500">Nama</span><strong class="block">{{ $reservation->name }}</strong></div>
        <div><span class="text-gray-500">NIK</span><strong class="block">{{ $reservation->masked_nik }}</strong></div>
        <div><span class="text-gray-500">Layanan</span><strong class="block">{{ $reservation->service?->name }}</strong></div>
        <div><span class="text-gray-500">Nomor antrean</span><strong class="block">{{ $reservation->queue?->number ?? 'Belum diterbitkan' }}</strong></div>
        <div><span class="text-gray-500">Tanggal</span><strong class="block">{{ $reservation->service_date->format('d-m-Y') }}</strong></div>
        <div><span class="text-gray-500">Sesi</span><strong class="block">{{ substr($reservation->session?->starts_at, 0, 5) }}–{{ substr($reservation->session?->ends_at, 0, 5) }}</strong></div>
    </div>
    <div class="border-t pt-3 dark:border-gray-700"><strong>Riwayat tindakan</strong>
        <div class="mt-2 space-y-2">
            @forelse($reservation->audits as $audit)
                <div class="rounded-lg bg-gray-50 p-2 dark:bg-gray-800"><b>{{ ucfirst(str_replace('_', ' ', $audit->action)) }}</b> · {{ $audit->created_at?->format('d-m-Y H:i') }}<br><span class="text-xs text-gray-500">{{ $audit->user?->name ?? 'Sistem/pemohon' }} @if(data_get($audit->metadata, 'reason'))— {{ data_get($audit->metadata, 'reason') }}@endif</span></div>
            @empty
                <span class="text-gray-500">Belum ada audit tindakan.</span>
            @endforelse
        </div>
    </div>
</div>
