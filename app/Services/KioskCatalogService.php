<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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

    /**
     * Instansi aktif, diurutkan menurut total tiket bulan berjalan.
     * Jumlah layanan aktif menjadi pembeda yang stabil ketika total tiket sama.
     *
     * @return Collection<int, Instansi>
     */
    public function rankedInstitutions(): Collection
    {
        $periodKey = now()->format('Y-m');

        return $this->cache->remember("kiosk:institutions:ranked:{$periodKey}:v1", function (): Collection {
            $from = now()->startOfMonth();
            $until = now()->endOfDay();
            $usageQuery = DB::table('queues as usage_queues')
                ->join('services as usage_services', 'usage_services.id', '=', 'usage_queues.service_id')
                ->selectRaw('COUNT(*)')
                ->whereColumn('usage_services.instansi_id', 'instansis.instansi_id')
                ->whereBetween('usage_queues.created_at', [$from, $until]);

            return Instansi::query()
                ->select('instansis.*')
                ->selectSub($usageQuery, 'monthly_queue_count')
                ->whereNotNull('counter_id')
                ->whereHas('services', fn ($query) => $query->where('is_active', true))
                ->withCount([
                    'services as active_services_count' => fn ($query) => $query->where('is_active', true),
                ])
                ->orderByDesc('monthly_queue_count')
                ->orderByDesc('active_services_count')
                ->orderBy('nama_instansi')
                ->get();
        }, 300);
    }

    /**
     * @return array{popular: Collection<int, Instansi>, others: Collection<int, Instansi>}
     */
    public function splitInstitutions(Collection $institutions): array
    {
        $maximum = max(1, min(8, (int) config('kiosk.popular_institution_count', 6)));
        $minimumTotal = config('kiosk.popular_minimum_total');

        $popular = $institutions->take($maximum);

        if ($minimumTotal !== null) {
            $aboveThreshold = $institutions
                ->filter(fn (Instansi $instansi): bool => (int) $instansi->monthly_queue_count >= (int) $minimumTotal)
                ->take($maximum);

            // Awal bulan tidak boleh menghasilkan kolom populer yang kosong.
            if ($aboveThreshold->isNotEmpty()) {
                $popular = $aboveThreshold;
            }
        }

        $popularIds = $popular->pluck('instansi_id');

        return [
            'popular' => $popular->values(),
            'others' => $institutions
                ->reject(fn (Instansi $instansi): bool => $popularIds->contains($instansi->instansi_id))
                ->values(),
        ];
    }

    /**
     * Tambahkan informasi antrean hanya untuk dua loket Konsultasi
     * Kependudukan Dispendukcapil agar pemohon dapat memilih loket yang sepi.
     *
     * @param Collection<int, Service> $services
     * @return Collection<int, Service>
     */
    public function withDisdukcapilConsultationQueueCounts(Collection $services): Collection
    {
        $consultationServices = $services
            ->filter(fn (Service $service): bool => in_array($service->prefix, ['3C-6', '3C-7'], true));

        if ($consultationServices->isEmpty()) {
            return $services;
        }

        $counts = Queue::query()
            ->whereIn('service_id', $consultationServices->pluck('id'))
            ->whereDate('created_at', today())
            ->whereIn('status', [
                Queue::STATUS_PRINTING,
                Queue::STATUS_WAITING,
                Queue::STATUS_CALLED,
                Queue::STATUS_SERVING,
            ])
            ->selectRaw('service_id, COUNT(*) as total')
            ->groupBy('service_id')
            ->pluck('total', 'service_id');

        $minimumCount = $consultationServices
            ->map(fn (Service $service): int => (int) ($counts[$service->id] ?? 0))
            ->min();

        return $services->map(function (Service $service) use ($counts, $minimumCount): Service {
            $isConsultationCounter = in_array($service->prefix, ['3C-6', '3C-7'], true);

            $service->setAttribute('is_disdukcapil_consultation_counter', $isConsultationCounter);
            $service->setAttribute('active_queue_count', $isConsultationCounter
                ? (int) ($counts[$service->id] ?? 0)
                : null);
            $service->setAttribute('is_recommended_consultation_counter', $isConsultationCounter
                && (int) ($counts[$service->id] ?? 0) === $minimumCount);

            return $service;
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
