<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\Queue;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementService
{
    public function latest(?string $afterId = null, ?int $zoneId = null): ?array
    {
        $query = Queue::with(['service', 'counter.instansi'])
            ->whereIn('status', [Queue::STATUS_CALLED, Queue::STATUS_SERVING])
            ->whereDate('created_at', now()->toDateString())
            ->where('called_at', '>=', now()->subMinutes(2));

        $this->scopeToZone($query, $zoneId);

        if ($cursor = $this->parseCursor($afterId)) {
            $query->where(function (Builder $query) use ($cursor): void {
                $query->where('called_at', '>', $cursor['called_at'])
                    ->orWhere(function (Builder $query) use ($cursor): void {
                        $query->where('called_at', '=', $cursor['called_at'])
                            ->where('id', '>', $cursor['queue_id']);
                    });
            });
        }

        $queue = $query
            ->orderBy('called_at')
            ->orderBy('id')
            ->first();

        if (! $queue) {
            return null;
        }

        $announcementId = $queue->id.':'.$queue->called_at->getTimestamp();

        return [
            'announcementId' => $announcementId,
            'queueId' => $queue->id,
            'queueNumber' => $queue->number,
            'serviceName' => $queue->service?->name ?? 'Layanan',
            'counterName' => $queue->counter?->name ?? 'Loket',
            'zona' => $queue->counter?->instansi?->nama_instansi ?? 'Zona',
            'calledAt' => $queue->called_at->format('H:i:s'),
        ];
    }

    private function scopeToZone(Builder $query, ?int $zoneId): void
    {
        if (! $zoneId) {
            return;
        }

        $zone = Counter::withoutGlobalScopes()->find($zoneId);

        if (! $zone) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('counter', function (Builder $query) use ($zone): void {
            $query->withoutGlobalScopes()
                ->where(function (Builder $query) use ($zone): void {
                    $query->whereKey($zone->id)
                        ->orWhere('name', $zone->name);
                });
        });
    }

    private function parseCursor(?string $afterId): ?array
    {
        if (! is_string($afterId)
            || preg_match('/^(\d+):(\d+)$/', $afterId, $matches) !== 1) {
            return null;
        }

        return [
            'queue_id' => (int) $matches[1],
            'called_at' => Carbon::createFromTimestamp(
                (int) $matches[2],
                (string) config('app.timezone'),
            ),
        ];
    }
}
