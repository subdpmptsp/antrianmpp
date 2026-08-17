<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\Instansi;
use Illuminate\Support\Collection;

class KioskCatalogService
{
    public function __construct(private readonly MasterDataCache $cache) {}

    public function zones(): array
    {
        return $this->cache->remember('kiosk:zones:v3', function (): array {
            $configuredZones = (array) config('tv.zones', []);
            $counters = Counter::withoutGlobalScopes()
                ->where('is_active', true)
                ->orderBy('id')
                ->get(['id', 'name']);
            $resolvedCounters = collect($configuredZones)
                ->map(fn (array $zone): ?Counter => $this->resolveCounter($zone, $counters));
            $counterIds = $resolvedCounters->filter()->pluck('id')->unique()->values();
            $institutions = Instansi::query()
                ->whereIn('counter_id', $counterIds)
                ->withCount([
                    'services as active_services_count' => fn ($query) => $query->where('is_active', true),
                ])
                ->orderBy('nama_instansi')
                ->get()
                ->groupBy('counter_id');
            $zones = [];

            foreach ($configuredZones as $zoneNumber => $configuredZone) {
                $zoneNumber = (int) $zoneNumber;
                $counter = $resolvedCounters->get($zoneNumber);
                $zoneInstitutions = $counter
                    ? $institutions->get($counter->id, collect())
                    : collect();

                $zones[$zoneNumber] = [
                    'name' => $counter?->name ?? (string) ($configuredZone['name'] ?? "ZONA {$zoneNumber}"),
                    'counter_id' => $counter?->id,
                    // Nama key dipertahankan agar view kiosk lama tetap kompatibel.
                    'services' => $zoneInstitutions->pluck('nama_instansi')->all(),
                    'institution_count' => $zoneInstitutions->count(),
                    'service_count' => (int) $zoneInstitutions->sum('active_services_count'),
                ];
            }

            return $zones;
        });
    }

    private function resolveCounter(array $zone, Collection $counters): ?Counter
    {
        $configuredCounterId = filter_var($zone['counter_id'] ?? null, FILTER_VALIDATE_INT);

        if ($configuredCounterId) {
            $configuredCounter = $counters->firstWhere('id', $configuredCounterId);

            if ($configuredCounter) {
                return $configuredCounter;
            }
        }

        $configuredName = mb_strtoupper((string) ($zone['name'] ?? ''));

        return $counters->first(
            fn (Counter $counter): bool => mb_strtoupper($counter->name) === $configuredName,
        );
    }
}
