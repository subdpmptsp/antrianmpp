<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonitoringRealtimeService
{
    public function getSummary(): array
    {
        $today = now()->toDateString();

        $counts = Queue::whereDate('created_at', $today)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = '".Queue::STATUS_WAITING."' THEN 1 ELSE 0 END) as menunggu")
            ->selectRaw("SUM(CASE WHEN status = '".Queue::STATUS_SERVING."' THEN 1 ELSE 0 END) as sedang_dilayani")
            ->selectRaw("SUM(CASE WHEN status = '".Queue::STATUS_FINISHED."' THEN 1 ELSE 0 END) as selesai")
            ->selectRaw("SUM(CASE WHEN status = '".Queue::STATUS_CANCELED."' THEN 1 ELSE 0 END) as batal")
            ->first();

        $avgWaitMinutes = $this->getAverageWaitMinutes($today);

        return [
            'total' => (int) ($counts->total ?? 0),
            'menunggu' => (int) ($counts->menunggu ?? 0),
            'sedang_dilayani' => (int) ($counts->sedang_dilayani ?? 0),
            'selesai' => (int) ($counts->selesai ?? 0),
            'batal' => (int) ($counts->batal ?? 0),
            'avg_wait_minutes' => $avgWaitMinutes,
        ];
    }

    public function getZones(): Collection
    {
        $today = now()->toDateString();

        $zones = collect(config('tv.zones', []))
            ->map(function (array $configuredZone, int|string $zoneNumber): array {
                return [
                    'zone_number' => (int) $zoneNumber,
                    'name' => (string) ($configuredZone['name'] ?? "ZONA {$zoneNumber}"),
                ];
            })
            ->values();

        // Satu zona dapat memiliki banyak loket fisik. Kelompokkan semuanya
        // berdasarkan nama zona agar kartu dan filter tetap tampil satu kali per zona.
        $counterIdsByZoneName = Counter::withoutGlobalScopes()
            ->whereIn('name', $zones->pluck('name'))
            ->get(['id', 'name'])
            ->groupBy('name')
            ->map(fn (Collection $counters) => $counters->pluck('id')->values());

        $zoneNumberByCounterId = $zones->flatMap(function (array $zone) use ($counterIdsByZoneName) {
            return $counterIdsByZoneName
                ->get($zone['name'], collect())
                ->mapWithKeys(fn (int $counterId): array => [$counterId => $zone['zone_number']]);
        });

        $servicesByZone = DB::table('services')
            ->join('instansis', 'instansis.instansi_id', '=', 'services.instansi_id')
            ->whereIn('instansis.counter_id', $zoneNumberByCounterId->keys())
            ->get(['services.id as service_id', 'instansis.counter_id'])
            ->map(fn ($service) => [
                'service_id' => $service->service_id,
                'zone_number' => $zoneNumberByCounterId->get($service->counter_id),
            ])
            ->filter(fn (array $service): bool => $service['zone_number'] !== null)
            ->groupBy('zone_number');

        $queueCounts = DB::table('queues')
            ->whereDate('created_at', $today)
            ->whereIn('status', [Queue::STATUS_WAITING, Queue::STATUS_SERVING])
            ->whereIn('service_id', $servicesByZone->flatten()->pluck('service_id'))
            ->selectRaw('service_id, status, COUNT(*) as total')
            ->groupBy('service_id', 'status')
            ->get()
            ->groupBy('service_id');

        $zoneStats = $zones->map(function (array $zone) use ($servicesByZone, $queueCounts) {
            $serviceIds = $servicesByZone->get($zone['zone_number'], collect())->pluck('service_id');
            $counts = $serviceIds
                ->flatMap(fn ($serviceId) => $queueCounts->get($serviceId, collect()));

            return [
                'id' => $zone['zone_number'],
                'zone_number' => $zone['zone_number'],
                'name' => $zone['name'],
                'menunggu' => (int) $counts->where('status', Queue::STATUS_WAITING)->sum('total'),
                'dilayani' => (int) $counts->where('status', Queue::STATUS_SERVING)->sum('total'),
            ];
        });

        $avgMenunggu = $zoneStats->avg('menunggu') ?: 0;
        $maxMenunggu = $zoneStats->max('menunggu') ?: 1;

        return $zoneStats->map(function (array $zone) use ($avgMenunggu, $maxMenunggu) {
            $isPadat = $zone['menunggu'] >= 3
                && $avgMenunggu > 0
                && $zone['menunggu'] > ($avgMenunggu * 1.5);

            $zone['is_padat'] = $isPadat;
            $zone['progress'] = $maxMenunggu > 0
                ? (int) round(($zone['menunggu'] / $maxMenunggu) * 100)
                : 0;

            return $zone;
        });
    }

    public function getZoneOptions(): Collection
    {
        return collect(config('tv.zones', []))
            ->mapWithKeys(function (array $configuredZone, int|string $zoneNumber): array {
                return [
                    (string) $zoneNumber => (string) ($configuredZone['name'] ?? "ZONA {$zoneNumber}"),
                ];
            })
            ->sortKeys();
    }

    public function getServices(?string $zoneId = null, ?string $search = null): Collection
    {
        $today = now()->toDateString();

        return Service::query()
            ->with(['instansi:instansi_id,nama_instansi'])
            ->withCount([
                'queues as menunggu_count' => fn ($q) => $q
                    ->where('status', Queue::STATUS_WAITING)
                    ->whereDate('created_at', $today),
                'queues as dipanggil_count' => fn ($q) => $q
                    ->where('status', Queue::STATUS_CALLED)
                    ->whereDate('created_at', $today),
                'queues as dilayani_count' => fn ($q) => $q
                    ->where('status', Queue::STATUS_SERVING)
                    ->whereDate('created_at', $today),
                'queues as selesai_count' => fn ($q) => $q
                    ->where('status', Queue::STATUS_FINISHED)
                    ->whereDate('created_at', $today),
                'queues as batal_count' => fn ($q) => $q
                    ->where('status', Queue::STATUS_CANCELED)
                    ->whereDate('created_at', $today),
            ])
            ->where('is_active', true)
            ->where('is_archived', false)
            ->when(filled($zoneId), function ($q) use ($zoneId): void {
                $zoneName = (string) config("tv.zones.{$zoneId}.name", "ZONA {$zoneId}");
                $counterIds = Counter::withoutGlobalScopes()
                    ->where('name', $zoneName)
                    ->pluck('id');

                $q->whereHas('instansi.counter', function ($query) use ($counterIds): void {
                    $query->whereIn('id', $counterIds);
                });
            })
            ->when(filled($search), fn ($q) => $q->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->get(['id', 'name', 'instansi_id']);
    }

    protected function getAverageWaitMinutes(string $today): ?float
    {
        $avg = DB::table('queues')
            ->whereDate('created_at', $today)
            ->whereIn('status', [Queue::STATUS_SERVING, Queue::STATUS_FINISHED])
            ->whereNotNull('served_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, served_at)) as avg_minutes')
            ->value('avg_minutes');

        return $avg !== null ? round((float) $avg, 0) : null;
    }
}
