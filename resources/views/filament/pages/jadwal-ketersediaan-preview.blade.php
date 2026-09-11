<x-filament-panels::page>
    <div class="mx-auto max-w-7xl space-y-5">
        <section class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-blue-900 shadow-sm dark:border-blue-800 dark:bg-blue-950/40 dark:text-blue-100">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-eye class="mt-0.5 h-6 w-6 shrink-0" />
                    <div>
                        <h2 class="font-bold">Mode pratinjau tampilan</h2>
                        <p class="mt-1 text-sm">Halaman ini hanya membaca konfigurasi yang sudah ada. Tidak ada perubahan jadwal yang disimpan dari sini.</p>
                    </div>
                </div>
                <x-filament::button tag="a" :href="\App\Filament\Pages\QueueOperatingSchedule::getUrl()" color="gray" icon="heroicon-o-arrow-left">
                    Kembali ke halaman aktif
                </x-filament::button>
            </div>
        </section>

        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Jadwal &amp; Ketersediaan Antrean</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Satu pusat untuk melihat jadwal, tanggal khusus, aturan loket, dan status penerbitan nomor.</p>
        </div>

        <nav class="flex max-w-full gap-1 overflow-x-auto rounded-xl bg-gray-100 p-1 dark:bg-gray-800" aria-label="Bagian jadwal dan ketersediaan">
            @foreach ([
                'ringkasan' => ['Ringkasan', 'heroicon-o-home'],
                'mingguan' => ['Jadwal Mingguan', 'heroicon-o-calendar-days'],
                'tanggal' => ['Tanggal Khusus', 'heroicon-o-calendar'],
                'loket' => ['Jadwal Loket', 'heroicon-o-building-storefront'],
                'lanjutan' => ['Pengaturan Lanjutan', 'heroicon-o-adjustments-horizontal'],
                'log' => ['Log Perubahan', 'heroicon-o-clock'],
            ] as $section => [$label, $icon])
                <button wire:click="selectSection('{{ $section }}')" type="button"
                    class="inline-flex shrink-0 items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition {{ $activeSection === $section ? 'bg-primary-600 text-white shadow-sm' : 'text-gray-600 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    <x-dynamic-component :component="$icon" class="h-4 w-4" />
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        @if ($activeSection === 'ringkasan')
            <div class="grid gap-4 md:grid-cols-3">
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center justify-between gap-3"><span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Jadwal hari ini</span><x-heroicon-o-clock class="h-5 w-5 text-primary-600" /></div>
                    @if ($this->todaySchedule['is_open'])
                        <p class="mt-3 text-2xl font-bold text-gray-950 dark:text-white">{{ $this->todaySchedule['opens_at'] }}–{{ $this->todaySchedule['closes_at'] }}</p>
                        <span class="mt-2 inline-flex rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-800 dark:bg-green-900/50 dark:text-green-200">Terjadwal buka</span>
                    @else
                        <p class="mt-3 text-2xl font-bold text-gray-950 dark:text-white">Tutup</p>
                        <span class="mt-2 inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">Tidak beroperasi</span>
                    @endif
                </section>
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center justify-between gap-3"><span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Jadwal khusus loket</span><x-heroicon-o-building-storefront class="h-5 w-5 text-primary-600" /></div>
                    <p class="mt-3 text-2xl font-bold text-gray-950 dark:text-white">{{ $this->customScheduleCount }} loket</p>
                    <p class="mt-2 text-xs text-gray-500">Memiliki aturan berbeda dari jadwal default.</p>
                </section>
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-center justify-between gap-3"><span class="text-sm font-semibold text-gray-600 dark:text-gray-300">Batas nomor baru</span><x-heroicon-o-adjustments-horizontal class="h-5 w-5 text-primary-600" /></div>
                    <p class="mt-3 text-2xl font-bold text-gray-950 dark:text-white">{{ $this->cutoffMinutes > 0 ? $this->cutoffMinutes.' menit' : 'Nonaktif' }}</p>
                    <p class="mt-2 text-xs text-gray-500">Sebelum jam tutup operasional.</p>
                </section>
            </div>

            <div class="grid gap-5 lg:grid-cols-[1.35fr_.85fr]">
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div><h2 class="font-bold">Jadwal mingguan default</h2><p class="mt-1 text-sm text-gray-500">Berlaku untuk loket tanpa jadwal khusus.</p></div>
                        <button wire:click="selectSection('mingguan')" type="button" class="text-sm font-semibold text-primary-600 hover:text-primary-500">Lihat lengkap →</button>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($this->weeklySchedule as $day)
                            <div class="flex items-center justify-between gap-3 rounded-xl bg-gray-50 px-4 py-3 dark:bg-gray-800">
                                <span class="text-sm font-semibold">{{ $this->dayName($day['day']) }}</span>
                                <span class="text-sm {{ $day['is_open'] ? 'text-gray-700 dark:text-gray-200' : 'italic text-gray-400' }}">{{ $day['is_open'] ? $day['opens_at'].'–'.$day['closes_at'] : 'Tutup' }}</span>
                            </div>
                        @endforeach
                    </div>
                </section>

                <aside class="rounded-2xl border border-primary-100 bg-primary-50 p-5 shadow-sm dark:border-primary-900 dark:bg-gray-900">
                    <div class="flex items-center gap-3"><x-heroicon-o-beaker class="h-6 w-6 text-primary-600" /><div><h2 class="font-bold">Simulasikan status</h2><p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Pratinjau susunan fitur simulator.</p></div></div>
                    <div class="mt-5 space-y-3 opacity-70">
                        <label class="block text-sm font-semibold">Loket<select disabled class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800"><option>Pilih loket pelayanan</option></select></label>
                        <div class="grid grid-cols-2 gap-3"><label class="text-sm font-semibold">Tanggal<input disabled type="text" placeholder="Pilih tanggal" class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800"></label><label class="text-sm font-semibold">Jam<input disabled type="text" placeholder="Pilih waktu" class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800"></label></div>
                        <x-filament::button disabled class="w-full">Belum diaktifkan</x-filament::button>
                    </div>
                </aside>
            </div>

            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 class="font-bold">Tanggal khusus berikutnya</h2><p class="mt-1 text-sm text-gray-500">Hari libur atau penutupan yang sudah tersimpan.</p></div>
                    <button wire:click="selectSection('tanggal')" type="button" class="text-sm font-semibold text-primary-600 hover:text-primary-500">Kelola tanggal →</button>
                </div>
                <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($this->upcomingHolidays as $holiday)
                        <div class="flex items-center justify-between gap-3 py-3 text-sm"><span class="font-semibold">{{ $holiday->name }}</span><span class="text-gray-500">{{ $holiday->date->translatedFormat('d M Y') }}</span></div>
                    @empty
                        <p class="py-4 text-sm text-gray-400">Belum ada tanggal khusus mendatang.</p>
                    @endforelse
                </div>
            </section>
        @elseif ($activeSection === 'mingguan')
            <section class="grid gap-5 lg:grid-cols-[1.3fr_.7fr]">
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <h2 class="font-bold">Jadwal mingguan default</h2><p class="mt-1 text-sm text-gray-500">Tampilan editor akan disambungkan setelah desain disetujui.</p>
                    <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->weeklySchedule as $day)
                            <div class="grid grid-cols-[90px_1fr_auto] items-center gap-4 py-3"><span class="font-semibold">{{ $this->dayName($day['day']) }}</span><span class="text-sm text-gray-500">{{ $day['is_open'] ? $day['opens_at'].' sampai '.$day['closes_at'] : 'Tutup sepanjang hari' }}</span><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $day['is_open'] ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }}">{{ $day['is_open'] ? 'Buka' : 'Tutup' }}</span></div>
                        @endforeach
                    </div>
                    <x-filament::button disabled class="mt-5">Simpan belum diaktifkan</x-filament::button>
                </div>
                <aside class="h-fit rounded-2xl bg-gray-50 p-5 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><h2 class="font-bold">Aturan utama</h2><ul class="mt-3 list-disc space-y-2 pl-5 text-sm text-gray-600 dark:text-gray-300"><li>Menjadi jadwal dasar semua loket.</li><li>Hari libur dan tanggal khusus dapat mengalahkan jadwal ini.</li><li>Loket tertentu dapat memiliki jadwal khusus.</li></ul></aside>
            </section>
        @elseif ($activeSection === 'tanggal')
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><h2 class="font-bold">Hari libur &amp; tanggal khusus</h2><p class="mt-1 text-sm text-gray-500">Nantinya memuat penutupan seluruh MPP dan aturan khusus satu layanan.</p><div class="mt-6 grid gap-4 md:grid-cols-2"><div class="rounded-xl bg-gray-50 p-5 dark:bg-gray-800"><x-heroicon-o-calendar-days class="h-7 w-7 text-primary-600" /><h3 class="mt-3 font-bold">Penutupan seluruh MPP</h3><p class="mt-2 text-sm text-gray-500">Libur nasional, cuti bersama, dan penutupan lokal.</p></div><div class="rounded-xl bg-gray-50 p-5 dark:bg-gray-800"><x-heroicon-o-briefcase class="h-7 w-7 text-primary-600" /><h3 class="mt-3 font-bold">Tanggal khusus layanan</h3><p class="mt-2 text-sm text-gray-500">Menutup satu layanan tanpa memengaruhi layanan lainnya.</p></div></div><x-filament::button disabled class="mt-5">Form belum diaktifkan</x-filament::button></section>
        @elseif ($activeSection === 'loket')
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><h2 class="font-bold">Jadwal khusus loket</h2><p class="mt-1 text-sm text-gray-500">Hanya untuk loket dengan jam berbeda dari jadwal default.</p><div class="mt-5 rounded-xl border border-dashed border-gray-300 p-8 text-center dark:border-gray-700"><x-heroicon-o-building-storefront class="mx-auto h-9 w-9 text-gray-400" /><p class="mt-3 font-semibold">{{ $this->customScheduleCount }} loket memiliki aturan khusus</p><p class="mt-1 text-sm text-gray-500">Editor dan daftar detail akan disambungkan pada fase fungsi.</p></div><x-filament::button disabled class="mt-5">Pengaturan belum diaktifkan</x-filament::button></section>
        @elseif ($activeSection === 'lanjutan')
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><h2 class="font-bold">Pengaturan lanjutan</h2><p class="mt-1 text-sm text-gray-500">Opsi yang jarang digunakan dipisahkan dari pengaturan harian.</p><div class="mt-5 grid gap-4 md:grid-cols-2"><div class="rounded-xl bg-gray-50 p-5 dark:bg-gray-800"><div class="flex items-center justify-between gap-3"><div><h3 class="font-bold">Cutoff nomor baru</h3><p class="mt-1 text-sm text-gray-500">Hentikan tiket sebelum jam tutup.</p></div><span class="text-sm font-semibold">{{ $this->cutoffMinutes > 0 ? $this->cutoffMinutes.' menit' : 'Nonaktif' }}</span></div></div><div class="rounded-xl bg-gray-50 p-5 dark:bg-gray-800"><div class="flex items-center justify-between gap-3"><div><h3 class="font-bold">Pengaman auto-reopen</h3><p class="mt-1 text-sm text-gray-500">Kembali ke jadwal default pada hari berikutnya.</p></div><span class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">Direncanakan</span></div></div></div><x-filament::button disabled class="mt-5">Simpan belum diaktifkan</x-filament::button></section>
        @else
            <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><h2 class="font-bold">Log perubahan</h2><p class="mt-1 text-sm text-gray-500">Riwayat perubahan jadwal, tanggal khusus, dan aturan loket akan ditampilkan di sini.</p><div class="mt-5 overflow-x-auto"><table class="w-full min-w-[620px] text-left text-sm"><thead class="border-b text-xs uppercase text-gray-500"><tr><th class="px-3 py-3">Waktu</th><th class="px-3 py-3">Pengguna</th><th class="px-3 py-3">Bagian</th><th class="px-3 py-3">Perubahan</th></tr></thead><tbody><tr><td colspan="4" class="px-3 py-10 text-center text-gray-400">Data log belum disambungkan pada mode pratinjau.</td></tr></tbody></table></div></section>
        @endif
    </div>
</x-filament-panels::page>
