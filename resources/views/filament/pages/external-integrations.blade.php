<x-filament-panels::page>
    @php
        $panrb = $this->panrbStatus();
        $dukcapil = $this->dukcapilStatus();
    @endphp

    <div class="space-y-6">
        <x-filament::section icon="heroicon-o-shield-check" icon-color="primary">
            <x-slot name="heading">Pusat konfigurasi koneksi eksternal</x-slot>
            <x-slot name="description">Credential disimpan di environment server dan tidak pernah dikirim ke browser. Halaman ini hanya menampilkan kesiapan konfigurasi yang aman.</x-slot>
            <div class="flex flex-wrap gap-2">
                <x-filament::badge color="gray" icon="heroicon-m-lock-closed">Secret tidak ditampilkan</x-filament::badge>
                <x-filament::badge color="gray" icon="heroicon-m-command-line">Konfigurasi melalui .env server</x-filament::badge>
                <x-filament::badge color="gray" icon="heroicon-m-document-text">Audit log disiapkan saat API tersedia</x-filament::badge>
            </div>
        </x-filament::section>

        <x-filament::tabs label="Pilih integrasi">
            <x-filament::tabs.item :active="$activeIntegration === 'overview'" icon="heroicon-m-squares-2x2" wire:click="selectIntegration('overview')">Ringkasan</x-filament::tabs.item>
            <x-filament::tabs.item :active="$activeIntegration === 'panrb'" icon="heroicon-m-document-check" wire:click="selectIntegration('panrb')">Pakta Integritas PANRB</x-filament::tabs.item>
            <x-filament::tabs.item :active="$activeIntegration === 'dukcapil'" icon="heroicon-m-identification" wire:click="selectIntegration('dukcapil')">Dukcapil</x-filament::tabs.item>
        </x-filament::tabs>

        @if ($activeIntegration === 'overview')
            <div class="grid gap-5 lg:grid-cols-2">
                <x-filament::section icon="heroicon-o-document-check" icon-color="danger">
                    <x-slot name="heading">Pakta Integritas Elektronik PANRB</x-slot>
                    <x-slot name="description">Pengiriman pakta, sinkronisasi master data, dan piagam digital.</x-slot>
                    <x-slot name="headerEnd"><x-filament::badge :color="$panrb['color']">{{ $panrb['label'] }}</x-filament::badge></x-slot>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-gray-500">Environment</dt><dd class="font-semibold">{{ ucfirst(config('external_integrations.panrb.environment', 'sandbox')) }}</dd></div>
                        <div><dt class="text-gray-500">Credential</dt><dd class="font-semibold">{{ $panrb['credential'] ? 'Terpasang di server' : 'Belum tersedia' }}</dd></div>
                        <div><dt class="text-gray-500">Base URL</dt><dd class="font-semibold">{{ filled(config('external_integrations.panrb.base_url')) ? 'Terpasang di server' : 'Belum tersedia' }}</dd></div>
                        <div><dt class="text-gray-500">Uji koneksi</dt><dd class="font-semibold">Belum pernah diuji</dd></div>
                    </dl>
                    <x-slot name="footerActions"><x-filament::button wire:click="selectIntegration('panrb')" icon="heroicon-m-cog-6-tooth">Lihat konfigurasi</x-filament::button></x-slot>
                </x-filament::section>

                <x-filament::section icon="heroicon-o-identification" icon-color="info">
                    <x-slot name="heading">Verifikasi Identitas Dukcapil</x-slot>
                    <x-slot name="description">Verifikasi NIK dan identitas pemohon antrean online.</x-slot>
                    <x-slot name="headerEnd"><x-filament::badge :color="$dukcapil['color']">{{ $dukcapil['label'] }}</x-filament::badge></x-slot>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div><dt class="text-gray-500">Provider</dt><dd class="font-semibold">{{ config('citizen_identity.driver') === 'disabled' ? 'Belum dipilih' : strtoupper(config('citizen_identity.driver')) }}</dd></div>
                        <div><dt class="text-gray-500">Credential</dt><dd class="font-semibold">{{ $dukcapil['credential'] ? 'Terpasang di server' : 'Belum tersedia' }}</dd></div>
                        <div><dt class="text-gray-500">Endpoint</dt><dd class="font-semibold">{{ filled(config('citizen_identity.endpoint')) ? 'Terpasang di server' : 'Belum tersedia' }}</dd></div>
                        <div><dt class="text-gray-500">Uji koneksi</dt><dd class="font-semibold">Belum pernah diuji</dd></div>
                    </dl>
                    <x-slot name="footerActions"><x-filament::button wire:click="selectIntegration('dukcapil')" icon="heroicon-m-cog-6-tooth">Lihat konfigurasi</x-filament::button></x-slot>
                </x-filament::section>
            </div>
        @elseif ($activeIntegration === 'panrb')
            @include('filament.pages.partials.external-integration-detail', [
                'title' => 'Pakta Integritas Elektronik PANRB',
                'description' => 'Rumah konfigurasi Skema I Integrasi API. Belum melakukan komunikasi ke server PANRB.',
                'status' => $panrb,
                'environment' => config('external_integrations.panrb.environment', 'sandbox'),
                'endpointItems' => [
                    ['Base URL', filled(config('external_integrations.panrb.base_url')) ? 'Terpasang di server' : 'Belum tersedia'],
                    ['Uji koneksi', config('external_integrations.panrb.endpoints.ping')],
                    ['Master data', config('external_integrations.panrb.endpoints.master_data')],
                    ['Kirim pakta', config('external_integrations.panrb.endpoints.submit')],
                ],
                'securityItems' => [
                    ['Header autentikasi', config('external_integrations.panrb.auth_header')],
                    ['API key', $panrb['credential'] ? 'Terpasang dan tersamarkan' : 'Belum tersedia'],
                    ['Timeout', config('external_integrations.panrb.timeout_seconds').' detik'],
                    ['Percobaan ulang', config('external_integrations.panrb.retry_times').' kali'],
                ],
                'usedBy' => ['Formulir Pakta pada kiosk', 'Pendaftaran antrean online', 'Penerbitan kode dan QR piagam', 'Sinkronisasi organisasi dan layanan'],
                'nextSteps' => ['Pasang base URL dan API key resmi', 'Uji GET /sync/ping', 'Sinkronkan master data', 'Petakan layanan lokal dan nasional', 'Lakukan UAT pengiriman pakta'],
            ])
        @else
            @include('filament.pages.partials.external-integration-detail', [
                'title' => 'Verifikasi Identitas Dukcapil',
                'description' => 'Konfigurasi verifikasi NIK dipusatkan dari modul Antrean Online ke halaman ini.',
                'status' => $dukcapil,
                'environment' => app()->environment('production') ? 'production' : 'testing',
                'endpointItems' => [
                    ['Endpoint verifikasi', filled(config('citizen_identity.endpoint')) ? 'Terpasang di server' : 'Belum tersedia'],
                    ['Endpoint kesehatan', filled(config('citizen_identity.health_endpoint')) ? 'Terpasang di server' : 'Belum tersedia'],
                    ['Method', config('citizen_identity.method', 'POST')],
                    ['Driver', config('citizen_identity.driver', 'disabled')],
                ],
                'securityItems' => [
                    ['Tipe autentikasi', config('citizen_identity.auth_type', 'bearer')],
                    ['Credential', $dukcapil['credential'] ? 'Terpasang dan tersamarkan' : 'Belum tersedia'],
                    ['Timeout', config('citizen_identity.timeout_seconds', 5).' detik'],
                    ['Jika API gagal', str_replace('_', ' ', config('citizen_identity.failure_mode', 'manual_review'))],
                ],
                'usedBy' => ['Verifikasi NIK pendaftar online', 'Pencocokan nama pemohon', 'Pencegahan reservasi ganda', 'Penandaan pemeriksaan identitas'],
                'nextSteps' => ['Terima dokumentasi dan credential resmi', 'Pasang credential di server', 'Uji endpoint koneksi', 'Validasi payload dan respons', 'Aktifkan setelah UAT lulus'],
            ])
        @endif
    </div>
</x-filament-panels::page>
