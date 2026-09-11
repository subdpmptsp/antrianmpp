<?php

namespace App\Services;

use App\Models\OnlineQueueSpecialWindow;
use App\Models\Service;
use Carbon\Carbon;

class OnlineQueueChannelPolicyService
{
    /** @return array{blocked: bool, message: string, window: ?OnlineQueueSpecialWindow} */
    public function onsitePolicy(Service $service, ?Carbon $at = null): array
    {
        $now = ($at ?: now())->setTimezone('Asia/Jakarta');
        $window = OnlineQueueSpecialWindow::query()
            ->where('service_id', $service->id)
            ->whereDate('date', $now->toDateString())
            ->where('mode', OnlineQueueSpecialWindow::MODE_ONLINE_ONLY)
            ->where('is_active', true)
            ->whereTime('starts_at', '<=', $now->format('H:i:s'))
            ->whereTime('ends_at', '>', $now->format('H:i:s'))
            ->first();

        if (! $window) {
            return ['blocked' => false, 'message' => '', 'window' => null];
        }

        return [
            'blocked' => true,
            'message' => 'Pengambilan nomor langsung untuk layanan ini sementara dialihkan ke antrean online sampai pukul '
                .substr((string) $window->ends_at, 0, 5).' WIB.',
            'window' => $window,
        ];
    }
}
