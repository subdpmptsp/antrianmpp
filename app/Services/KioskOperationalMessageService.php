<?php

namespace App\Services;

use App\Models\QueueOperatingSetting;
use Carbon\Carbon;

class KioskOperationalMessageService
{
    /** @return array<string, array{title: string, body: string, footer: string}> */
    public static function defaults(): array
    {
        return [
            'pre_opening' => [
                'title' => 'Antrean Belum Dibuka',
                'body' => 'Pengambilan nomor antrean akan dimulai pukul {jam_buka} WIB.',
                'footer' => 'Silakan menunggu hingga waktu operasional dimulai.',
            ],
            'friday_break' => [
                'title' => 'Jeda Istirahat Salat Jumat',
                'body' => 'Pengambilan nomor antrean dihentikan sementara.',
                'footer' => 'Silakan kembali setelah waktu buka kembali.',
            ],
            'closed' => [
                'title' => 'Pelayanan Hari Ini Telah Selesai',
                'body' => 'Pengambilan nomor antrean untuk hari ini telah ditutup.',
                'footer' => 'Terima kasih atas pengertian Anda.',
            ],
        ];
    }

    /** @return array{title: string, body: string, footer: string} */
    public function for(string $state, Carbon $opensAt): array
    {
        $stored = (array) QueueOperatingSetting::query()->first()?->kiosk_messages;
        $message = array_merge(self::defaults()[$state], (array) ($stored[$state] ?? []));
        $replacements = [
            '{jam_buka}' => $opensAt->format('H.i'),
            '{hari_buka}' => $opensAt->locale('id')->translatedFormat('l'),
            '{tanggal_buka}' => $opensAt->locale('id')->translatedFormat('d F Y'),
        ];

        return collect($message)->map(fn (mixed $value): string => strtr((string) $value, $replacements))->all();
    }
}
