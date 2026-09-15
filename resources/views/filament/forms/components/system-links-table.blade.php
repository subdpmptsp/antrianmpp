<div class="space-y-4">
    <div>
        <h3 class="text-base font-semibold text-gray-950 dark:text-white">Tautan Sistem</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Link dibuat otomatis dari alamat sistem. Klik Buka untuk membuka halaman di tab baru.</p>
    </div>

    <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-gray-700">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                <tr>
                    <th scope="col" class="px-4 py-3 font-semibold">Nama halaman</th>
                    <th scope="col" class="px-4 py-3 font-semibold">Link</th>
                    <th scope="col" class="w-24 px-4 py-3 text-right font-semibold">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                @foreach ([
                    ['Landing Antrean Online', route('online-queue.index'), 'Halaman pendaftaran antrean masyarakat.'],
                    ['Cari Reservasi', route('online-queue.lookup'), 'Pencarian kembali tiket dan status reservasi.'],
                    ['Dashboard MPP', route('showcase.siola-data'), 'Dashboard informasi publik MPP.'],
                    ['Mesin Cetak Antrean', route('public.queue-kiosk'), 'Khusus perangkat kiosk yang sudah terdaftar.'],
                    ['Kiosk Check-in Online', route('online-queue.checkin.kiosk'), 'Layar QR check-in untuk antrean online.'],
                ] as [$name, $link, $description])
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-950 dark:text-white">{{ $name }}</p>
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $description }}</p>
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-300">{{ $link }}</td>
                        <td class="px-4 py-3 text-right">
                            <x-filament::button tag="a" :href="$link" target="_blank" rel="noopener" color="gray" size="sm" icon="heroicon-o-arrow-top-right-on-square">
                                Buka
                            </x-filament::button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-xs text-amber-700 dark:text-amber-300">Mesin Cetak Antrean dan Kiosk Check-in Online dipakai pada perangkat kiosk, bukan untuk penggunaan umum dari komputer admin.</p>
</div>
