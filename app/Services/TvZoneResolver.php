<?php

namespace App\Services;

use App\Models\Counter;

class TvZoneResolver
{
    public function __construct(private readonly MasterDataCache $cache) {}

    public function resolve(int $zoneNumber): ?Counter
    {
        $zone = config("tv.zones.{$zoneNumber}");

        if (! is_array($zone)) {
            return null;
        }

        $configuredCounterId = filter_var($zone['counter_id'] ?? null, FILTER_VALIDATE_INT);
        $cacheKey = "tv-zone-v2:{$zoneNumber}:counter-id";
        $counterId = $this->cache->remember(
            $cacheKey,
            fn () => $this->findCounter($configuredCounterId, $zone)?->getKey(),
        );

        if ($counterId) {
            $counter = Counter::withoutGlobalScopes()->find($counterId);

            if ($counter) {
                return $counter;
            }

            // Database dapat berubah setelah restore tanpa memicu event model.
            $this->cache->invalidate();
        }

        return $this->findCounter($configuredCounterId, $zone);
    }

    public function fallbackName(int $zoneNumber): string
    {
        return (string) config("tv.zones.{$zoneNumber}.name", "ZONA {$zoneNumber}");
    }

    private function findCounter(int|false|null $configuredCounterId, array $zone): ?Counter
    {
        if ($configuredCounterId) {
            $configuredCounter = Counter::withoutGlobalScopes()->find($configuredCounterId);

            if ($configuredCounter) {
                return $configuredCounter;
            }
        }

        return Counter::withoutGlobalScopes()
            ->whereRaw('UPPER(name) = UPPER(?)', [$zone['name']])
            ->orderBy('id')
            ->first();
    }
}
