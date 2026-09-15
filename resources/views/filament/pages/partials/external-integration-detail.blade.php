<div class="space-y-5">
    <x-filament::section :icon="$title === 'Verifikasi Identitas Dukcapil' ? 'heroicon-o-identification' : 'heroicon-o-document-check'" icon-color="primary">
        <x-slot name="heading">{{ $title }}</x-slot>
        <x-slot name="description">{{ $description }}</x-slot>
        <x-slot name="headerEnd"><x-filament::badge :color="$status['color']">{{ $status['label'] }}</x-filament::badge></x-slot>
        <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 text-sm text-primary-800 dark:border-primary-800 dark:bg-primary-950 dark:text-primary-200">
            Environment: <strong>{{ ucfirst($environment) }}</strong>. Secret hanya dapat diisi oleh administrator server melalui <code>.env</code> dan tidak dapat dilihat dari panel ini.
        </div>
        <x-slot name="footerActions">
            <x-filament::button wire:click="testConnection('{{ $activeIntegration }}')" wire:loading.attr="disabled" wire:target="testConnection('{{ $activeIntegration }}')" icon="heroicon-m-signal">
                <span wire:loading.remove wire:target="testConnection('{{ $activeIntegration }}')">Uji koneksi</span>
                <span wire:loading wire:target="testConnection('{{ $activeIntegration }}')">Menguji…</span>
            </x-filament::button>
            <x-filament::button disabled color="gray" icon="heroicon-m-arrow-path" tooltip="Diaktifkan setelah struktur master data resmi dikonfirmasi">Sinkronkan master data</x-filament::button>
        </x-slot>
    </x-filament::section>

    <div class="grid gap-5 xl:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">Endpoint dan koneksi</x-slot>
            <x-slot name="description">Alamat yang dipakai backend saat integrasi resmi sudah diaktifkan.</x-slot>
            <dl class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($endpointItems as [$label, $value])
                    <div class="grid gap-1 py-3 sm:grid-cols-[170px_1fr]"><dt class="text-sm text-gray-500">{{ $label }}</dt><dd class="break-all text-sm font-semibold">{{ $value ?: 'Belum tersedia' }}</dd></div>
                @endforeach
            </dl>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">Keamanan dan ketahanan</x-slot>
            <x-slot name="description">Nilai rahasia tidak pernah dimuat ke HTML atau JavaScript.</x-slot>
            <dl class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($securityItems as [$label, $value])
                    <div class="grid gap-1 py-3 sm:grid-cols-[170px_1fr]"><dt class="text-sm text-gray-500">{{ $label }}</dt><dd class="break-all text-sm font-semibold">{{ $value ?: 'Belum tersedia' }}</dd></div>
                @endforeach
            </dl>
        </x-filament::section>
    </div>

    <div class="grid gap-5 xl:grid-cols-[1.1fr_.9fr]">
        <x-filament::section icon="heroicon-o-cursor-arrow-rays" icon-color="info">
            <x-slot name="heading">Digunakan oleh</x-slot>
            <x-slot name="description">Daftar fitur yang akan memakai integrasi ini ketika sudah aktif.</x-slot>
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($usedBy as $feature)
                    <div class="flex items-center gap-3 rounded-xl border border-gray-200 p-3 dark:border-white/10">
                        <x-heroicon-m-check-circle class="h-5 w-5 shrink-0 text-primary-600" />
                        <span class="text-sm font-medium">{{ $feature }}</span>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        <x-filament::section icon="heroicon-o-chart-bar" icon-color="success">
            <x-slot name="heading">Pemakaian dan pengaman</x-slot>
            <x-slot name="description">Angka saat ini hanya menghitung pengujian koneksi, bukan kuota provider.</x-slot>
            <dl class="divide-y divide-gray-200 dark:divide-white/10">
                <div class="flex items-center justify-between gap-3 py-3"><dt class="text-sm text-gray-500">Request 1 menit terakhir</dt><dd class="text-lg font-bold">{{ $this->requestsLastMinute }}</dd></div>
                <div class="flex items-center justify-between gap-3 py-3"><dt class="text-sm text-gray-500">Batas uji koneksi admin</dt><dd class="text-sm font-semibold">3 RPM/admin</dd></div>
                <div class="flex items-center justify-between gap-3 py-3"><dt class="text-sm text-gray-500">Rate limit provider</dt><dd><x-filament::badge color="warning">Menunggu dokumentasi</x-filament::badge></dd></div>
                <div class="flex items-center justify-between gap-3 py-3"><dt class="text-sm text-gray-500">Secret di browser</dt><dd><x-filament::badge color="success">Tidak pernah</x-filament::badge></dd></div>
            </dl>
        </x-filament::section>
    </div>

    <x-filament::section icon="heroicon-o-list-bullet" icon-color="gray">
        <x-slot name="heading">Riwayat uji koneksi</x-slot>
        <x-slot name="description">Respons disimpan tanpa API key, token, endpoint privat, maupun isi data kependudukan.</x-slot>
        {{ $this->table }}
    </x-filament::section>

    <x-filament::section icon="heroicon-o-link" icon-color="warning">
        <x-slot name="heading">Master data dan pemetaan layanan</x-slot>
        <x-slot name="description">Daftar organisasi/layanan akan tampil di sini setelah format respons resmi tersedia dan sinkronisasi pertama berhasil.</x-slot>
        <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-white/20">
            <x-heroicon-o-table-cells class="mx-auto h-9 w-9 text-gray-400" />
            <p class="mt-3 text-sm font-semibold">Belum ada master data yang dapat dipetakan</p>
            <p class="mt-1 text-sm text-gray-500">Nantinya tabel Filament menyediakan pencarian, status pemetaan, dan pagination.</p>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Tahapan aktivasi</x-slot>
        <x-slot name="description">Urutan ini mencegah integrasi aktif sebelum dokumentasi, pemetaan, dan pengujian selesai.</x-slot>
        <ol class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            @foreach ($nextSteps as $index => $step)
                <li class="flex gap-3 rounded-xl border border-gray-200 p-3 dark:border-white/10"><x-filament::badge color="primary">{{ $index + 1 }}</x-filament::badge><span class="text-sm font-medium">{{ $step }}</span></li>
            @endforeach
        </ol>
    </x-filament::section>
</div>
