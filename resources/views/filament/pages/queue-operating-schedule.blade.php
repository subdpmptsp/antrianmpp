<x-filament-panels::page>
    <style>
        .weekly-schedule-list {
            max-width: 590px;
        }

        .weekly-schedule-row {
            display: grid;
            grid-template-columns: 40px 92px minmax(254px, 1fr);
            align-items: center;
            column-gap: 10px;
        }

        .weekly-time-range {
            display: grid;
            grid-template-columns: minmax(0, 118px) 18px minmax(0, 118px);
            align-items: center;
            gap: 10px;
        }

        .weekly-closed {
            grid-column: 3;
        }

        .weekly-friday-card {
            grid-column: 2 / -1;
            width: 100%;
            min-width: 0;
            margin-top: 12px;
        }

        @media (max-width: 640px) {
            .weekly-schedule-section {
                padding: 18px 16px;
            }

            .weekly-schedule-list {
                max-width: none;
            }

            .weekly-schedule-row {
                grid-template-columns: 42px minmax(0, 1fr);
                row-gap: 12px;
                padding-block: 16px;
            }

            .weekly-time-range {
                grid-column: 1 / -1;
                grid-template-columns: minmax(0, 1fr) 14px minmax(0, 1fr);
                width: 100%;
                gap: 7px;
            }

            .weekly-closed,
            .weekly-friday-card {
                grid-column: 1 / -1;
            }

            .weekly-friday-card {
                margin-top: 2px;
                padding: 14px;
            }
        }
    </style>

    <div class="mx-auto w-full max-w-6xl space-y-5">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">Jadwal Operasional Antrean</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Satu pusat pengaturan untuk seluruh antrean reguler kiosk.</p>
            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Setiap perubahan diterapkan oleh server ketika nomor antrean diminta.</p>
        </div>

        <div class="inline-flex max-w-full flex-wrap gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-800" role="tablist" aria-label="Pengaturan jadwal operasional">
            @foreach (['mingguan' => 'Jadwal Mingguan', 'cutoff' => 'Cutoff & Kuota', 'loket' => 'Override Loket', 'tanggal_khusus' => 'Hari Libur & Tanggal Khusus', 'pesan_kiosk' => 'Pesan Kiosk', 'log' => 'Log Perubahan'] as $tab => $label)
                <button wire:click="selectTab('{{ $tab }}')" type="button"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $activeTab === $tab ? 'bg-primary-600 text-white shadow-sm hover:bg-primary-500' : 'text-gray-600 hover:bg-white dark:text-gray-300 dark:hover:bg-gray-700' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        @if ($activeTab === 'mingguan')
            <div class="mx-auto w-full max-w-6xl">
                <section class="weekly-schedule-section rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="mb-4"><h2 class="font-bold">Jadwal mingguan default</h2><p class="text-sm text-gray-500">Berlaku ke semua loket yang tidak memakai jadwal kustom.</p></div>
                    <div class="weekly-schedule-list divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($weeklySchedule as $index => $day)
                            <div class="weekly-schedule-row py-3">
                                <label class="relative inline-flex cursor-pointer items-center" style="display:inline-flex;align-items:center;" aria-label="Buka antrean hari {{ $this->dayName((int) $day['day']) }}">
                                    <input wire:model.live="weeklySchedule.{{ $index }}.is_open" type="checkbox" class="sr-only">
                                    <span style="position:relative;display:inline-block;width:36px;height:20px;border-radius:9999px;background:{{ $day['is_open'] ? '#16a34a' : '#9ca3af' }};transition:background .15s ease;">
                                        <span style="position:absolute;top:2px;left:2px;width:16px;height:16px;border-radius:9999px;background:#fff;box-shadow:0 1px 2px rgba(0,0,0,.15);transform:translateX({{ $day['is_open'] ? '16px' : '0' }});transition:transform .15s ease;"></span>
                                    </span>
                                </label>
                                <span class="text-sm font-medium">{{ $this->dayName((int) $day['day']) }}</span>
                                @if ($day['is_open'])
                                    <div class="weekly-time-range">
                                        <livewire:modern-picker type="time" wire:model.live="weeklySchedule.{{ $index }}.opens_at" :key="'weekly-open-'.$index" />
                                        <span class="text-center text-gray-400">–</span>
                                        <livewire:modern-picker type="time" wire:model.live="weeklySchedule.{{ $index }}.closes_at" :key="'weekly-close-'.$index" />
                                    </div>
                                @else
                                    <p class="weekly-closed text-sm italic text-gray-400">Tutup sepanjang hari</p>
                                @endif
                                @if ((int) $day['day'] === 5 && $day['is_open'])
                                    <div class="weekly-friday-card grid grid-cols-1 gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950 sm:grid-cols-2 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-100">
                                        <div class="sm:col-span-2">
                                            <span class="font-semibold">Jeda Salat Jumat</span>
                                            <p class="mt-0.5 text-xs text-amber-700 dark:text-amber-300">Atur waktu penghentian sementara dan waktu pelayanan dibuka kembali.</p>
                                        </div>
                                        <label class="block font-medium">Mulai jeda
                                            <livewire:modern-picker type="time" wire:model.live="weeklySchedule.{{ $index }}.break_starts_at" :key="'weekly-break-start-'.$index" />
                                        </label>
                                        <label class="block font-medium">Buka kembali
                                            <livewire:modern-picker type="time" wire:model.live="weeklySchedule.{{ $index }}.break_ends_at" :key="'weekly-break-end-'.$index" />
                                        </label>
                                        <small class="text-amber-700 sm:col-span-2 dark:text-amber-300">Kosongkan kedua kolom bila tidak memakai jeda. Selama jeda, kiosk menampilkan pemberitahuan dan tidak menerbitkan nomor.</small>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <button wire:click="saveGlobal" type="button" class="mt-5 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow hover:bg-primary-500">Simpan Perubahan</button>
                </section>
            </div>
        @elseif ($activeTab === 'cutoff')
            <section class="mx-auto min-h-[420px] w-full max-w-6xl rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h2 class="font-bold">Batas pengambilan nomor dan kuota</h2><p class="mt-1 text-sm text-gray-500">Pengunjung yang sudah memiliki tiket tetap dapat dilayani sampai selesai.</p>
                <div class="mt-6 grid max-w-3xl gap-5 sm:grid-cols-2"><label class="text-sm font-semibold">Berhenti <input wire:model="cutoffMinutes" type="number" min="0" class="fi-input mx-2 inline-block w-24 rounded-lg border-gray-300 text-center dark:border-gray-700 dark:bg-gray-800"> menit sebelum jam tutup</label><label class="text-sm font-semibold">Kuota harian default (opsional)<input wire:model="defaultDailyQuota" type="number" min="1" placeholder="Tanpa batas" class="fi-input mt-2 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800"></label></div>
                <div class="mt-5 rounded-xl bg-blue-50 p-4 text-sm text-blue-900 dark:bg-blue-950 dark:text-blue-100"><b>Contoh:</b> jika loket tutup pukul 16.00 dan cutoff {{ $cutoffMinutes }} menit, kiosk berhenti menerbitkan nomor pukul {{ \Carbon\Carbon::createFromTime(16, 0)->subMinutes((int) $cutoffMinutes)->format('H.i') }}. Kuota kosong berarti tidak dibatasi jumlahnya.</div>
                <button wire:click="saveGlobal" type="button" class="mt-5 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white">Simpan aturan global</button>
            </section>
        @elseif ($activeTab === 'loket')
            <div class="mx-auto grid min-h-[420px] w-full max-w-6xl gap-5 lg:grid-cols-[.9fr_1.1fr]">
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><h2 class="font-bold">Pilih loket</h2><p class="mt-1 text-sm text-gray-500">Secara default setiap loket mengikuti jadwal umum.</p><select wire:model.live="selectedCounterId" class="fi-input mt-5 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800">@foreach ($this->counters as $counter)<option value="{{ $counter->id }}">{{ $counter->display_name }} — {{ $counter->service?->name }}</option>@endforeach</select><div class="mt-4 rounded-xl bg-gray-50 p-4 text-sm text-gray-600 dark:bg-gray-800 dark:text-gray-300">Gunakan override hanya untuk loket yang benar-benar mempunyai jam berbeda atau kondisi khusus. Semua aksi dicatat.</div></section>
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><h2 class="font-bold">Aturan untuk loket terpilih</h2><label class="mt-4 block text-sm font-medium">Mode<select wire:model.live="overrideMode" class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800"><option value="default">Ikuti jadwal default</option><option value="custom">Jadwal kustom</option><option value="force_closed">Paksa tutup sementara</option><option value="force_open">Paksa buka sementara</option></select></label>
                    @if ($overrideMode === 'custom')<div class="mt-4 divide-y divide-gray-100 rounded-xl border border-gray-200 px-4 dark:divide-gray-800 dark:border-gray-700">@foreach ($overrideSchedule as $index => $day)<div class="grid items-center gap-2 py-2 sm:grid-cols-[auto_1fr]"><label class="flex items-center gap-2 text-sm"><input wire:model.live="overrideSchedule.{{ $index }}.is_open" type="checkbox" class="rounded text-primary-600">{{ $this->dayName((int) $day['day']) }}</label>@if($day['is_open'])<div class="grid grid-cols-2 gap-2"><livewire:modern-picker type="time" wire:model.live="overrideSchedule.{{ $index }}.opens_at" :key="'override-open-'.$index" /><livewire:modern-picker type="time" wire:model.live="overrideSchedule.{{ $index }}.closes_at" :key="'override-close-'.$index" /></div>@else <span class="text-sm italic text-gray-400">Tutup</span>@endif</div>@endforeach</div>@endif
                    @if ($overrideMode !== 'default')<label class="mt-4 block text-sm font-medium">Alasan<textarea wire:model="overrideReason" rows="2" class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800" placeholder="Wajib untuk keamanan dan audit"></textarea></label><div class="mt-3"><livewire:modern-picker type="datetime" label="Berlaku sampai (opsional)" wire:model.live="overrideValidUntil" key="override-valid-until" /></div>@endif
                    <button wire:click="saveCounterOverride" type="button" class="mt-5 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white">Simpan pengaturan loket</button></section>
            </div>
        @elseif ($activeTab === 'tanggal_khusus')
            <div class="mx-auto grid min-h-[420px] w-full max-w-6xl gap-5 xl:grid-cols-2">
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><h2 class="font-bold">Hari libur / penutupan seluruh MPP</h2><p class="mt-1 text-sm text-gray-500">Prioritas tertinggi: semua kiosk tutup pada tanggal ini.</p><div class="mt-4 grid gap-3 sm:grid-cols-2"><livewire:modern-picker type="date" label="Tanggal" wire:model.live="holidayDate" key="holiday-date" /><input wire:model="holidayName" placeholder="Contoh: Libur Nasional" class="fi-input rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800"><select wire:model="holidayType" class="fi-input rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800"><option value="national">Libur nasional</option><option value="collective">Cuti bersama</option><option value="local">Penutupan lokal MPP</option></select><input wire:model="holidayNotes" placeholder="Catatan (opsional)" class="fi-input rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800"></div><button wire:click="addHoliday" type="button" class="mt-4 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white">Simpan hari libur</button><div class="mt-5 divide-y divide-gray-100 dark:divide-gray-800">@forelse($this->holidays as $holiday)<div class="flex items-center justify-between gap-3 py-2 text-sm"><span><b>{{ $holiday->date->format('d M Y') }}</b> · {{ $holiday->name }}</span><button wire:click="deleteHoliday({{ $holiday->id }})" class="text-danger-600">Hapus</button></div>@empty <p class="py-3 text-sm text-gray-400">Belum ada data.</p>@endforelse</div></section>
                <section class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><h2 class="font-bold">Tanggal khusus satu layanan</h2><p class="mt-1 text-sm text-gray-500">Untuk penutupan layanan tertentu tanpa mematikan layanan lain.</p><div class="mt-4 grid gap-3 sm:grid-cols-2"><livewire:modern-picker type="date" label="Tanggal" wire:model.live="serviceClosureDate" key="service-closure-date" /><select wire:model="serviceClosureServiceId" class="fi-input rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800"><option value="">Pilih layanan</option>@foreach($this->services as $service)<option value="{{ $service->id }}">{{ $service->instansi?->nama_instansi }} — {{ $service->name }}</option>@endforeach</select><input wire:model="serviceClosureReason" class="fi-input sm:col-span-2 rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800" placeholder="Alasan penutupan"></div><button wire:click="addServiceClosure" type="button" class="mt-4 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white">Simpan tanggal khusus</button><div class="mt-5 divide-y divide-gray-100 dark:divide-gray-800">@forelse($this->serviceClosures as $closure)<div class="flex items-center justify-between gap-3 py-2 text-sm"><span><b>{{ $closure->date->format('d M Y') }}</b> · {{ $closure->service?->name }}<br><span class="text-gray-500">{{ $closure->reason }}</span></span><button wire:click="deleteServiceClosure({{ $closure->id }})" class="text-danger-600">Hapus</button></div>@empty <p class="py-3 text-sm text-gray-400">Belum ada data.</p>@endforelse</div></section>
            </div>
        @elseif ($activeTab === 'pesan_kiosk')
            <section class="mx-auto min-h-[420px] w-full max-w-6xl rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="max-w-3xl">
                    <h2 class="font-bold">Pesan operasional kiosk</h2>
                    <p class="mt-1 text-sm text-gray-500">Ubah redaksi modal layar penuh tanpa mengubah logika jadwal. Jam dan hari selalu mengikuti jadwal operasional.</p>
                    <div class="mt-4 rounded-xl bg-blue-50 p-4 text-sm text-blue-900 dark:bg-blue-950 dark:text-blue-100">
                        Placeholder otomatis: <code>{jam_buka}</code>, <code>{hari_buka}</code>, dan <code>{tanggal_buka}</code>. Contoh: <b>Antrean dimulai pukul {jam_buka} WIB.</b>
                    </div>
                </div>

                <div class="mt-6 grid gap-5 xl:grid-cols-3">
                    @foreach (['pre_opening' => ['Sebelum jam buka', 'Tampil otomatis pada pagi hari sebelum antrean dibuka.'], 'friday_break' => ['Jeda Salat Jumat', 'Tampil selama rentang jeda Jumat yang diatur.'], 'closed' => ['Setelah layanan tutup', 'Tampil setelah cutoff, hari libur, atau hari tutup.']] as $state => [$heading, $hint])
                        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                            <h3 class="font-semibold">{{ $heading }}</h3>
                            <p class="mt-1 min-h-10 text-xs text-gray-500">{{ $hint }}</p>
                            <label class="mt-4 block text-sm font-medium">Judul
                                <input wire:model="kioskMessages.{{ $state }}.title" type="text" maxlength="100" class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800">
                            </label>
                            <label class="mt-3 block text-sm font-medium">Isi utama
                                <textarea wire:model="kioskMessages.{{ $state }}.body" rows="3" maxlength="300" class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800"></textarea>
                            </label>
                            <label class="mt-3 block text-sm font-medium">Catatan bawah
                                <textarea wire:model="kioskMessages.{{ $state }}.footer" rows="2" maxlength="200" class="fi-input mt-1 block w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800"></textarea>
                            </label>
                        </div>
                    @endforeach
                </div>
                <button wire:click="saveKioskMessages" type="button" class="mt-5 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white">Simpan Pesan Kiosk</button>
            </section>
        @else
            <section class="mx-auto min-h-[420px] w-full max-w-6xl rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"><h2 class="font-bold">Log perubahan override loket</h2><p class="mt-1 text-sm text-gray-500">Riwayat paksa buka, paksa tutup, jadwal kustom, dan pengembalian ke default.</p><div class="mt-5 overflow-x-auto"><table class="w-full min-w-[620px] text-left text-sm"><thead class="border-b text-xs uppercase text-gray-500"><tr><th class="px-3 py-2">Waktu</th><th class="px-3 py-2">Loket</th><th class="px-3 py-2">Aksi</th><th class="px-3 py-2">Alasan</th><th class="px-3 py-2">Berlaku sampai</th></tr></thead><tbody class="divide-y divide-gray-100 dark:divide-gray-800">@forelse($this->overrideLogs as $log)<tr><td class="px-3 py-3">{{ $log->created_at->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td><td class="px-3 py-3 font-medium">{{ $log->counter?->display_name }}</td><td class="px-3 py-3">{{ str_replace('_', ' ', $log->action) }}</td><td class="px-3 py-3">{{ $log->reason ?: '–' }}</td><td class="px-3 py-3">{{ $log->valid_until?->timezone('Asia/Jakarta')->format('d M Y H:i') ?: 'Sampai diubah' }}</td></tr>@empty <tr><td colspan="5" class="px-3 py-6 text-center text-gray-400">Belum ada perubahan override.</td></tr>@endforelse</tbody></table></div></section>
        @endif
    </div>
</x-filament-panels::page>
