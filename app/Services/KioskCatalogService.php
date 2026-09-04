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
            $counterByZone = Counter::withoutGlobalScopes()
                ->join('instansis', 'instansis.instansi_id', '=', 'counters.instansi_id')
                ->where('counters.is_active', true)
                ->where('counters.is_archived', false)
                ->selectRaw('instansis.zone, MIN(counters.id) as counter_id')
                ->groupBy('instansis.zone')
                ->pluck('counter_id', 'zone');
            $institutions = Instansi::query()
                ->where('is_active', true)
                ->where('is_archived', false)
                ->withCount([
                    'services as active_services_count' => fn ($query) => $query
                        ->where('is_active', true)
                        ->where('is_archived', false),
                ])
                ->orderBy('nama_instansi')
                ->get()
                ->groupBy('zone');
            $zones = [];

            foreach ($configuredZones as $zoneNumber => $configuredZone) {
                $zoneNumber = (int) $zoneNumber;
                $zoneName = (string) ($configuredZone['name'] ?? "ZONA {$zoneNumber}");
                $zoneInstitutions = $institutions->get($zoneName, collect());

                $zones[$zoneNumber] = [
                    'name' => $zoneName,
                    // Dipertahankan untuk kompatibilitas URL TV lama. Zona tidak
                    // lagi diturunkan dari counter ini.
                    'counter_id' => $counterByZone->get($zoneName),
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
        // Katalog hanya memuat sekitar puluhan instansi dan menjadi pintu utama kiosk.
        // Membacanya langsung lebih aman daripada mempertahankan cache lama setelah admin
        // menambah layanan atau memindahkan relasi loket.
        return $this->queryRankedInstitutions();
    }

    private function queryRankedInstitutions(): Collection
    {
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
            ->where('is_active', true)
            ->where('is_archived', false)
            ->whereHas('counters', fn ($query) => $query
                ->where('is_active', true)
                ->where('is_archived', false)
                ->whereNotNull('service_id'))
            ->whereHas('services', fn ($query) => $query
                ->where('is_active', true)
                ->where('is_archived', false))
            ->withCount([
                'services as active_services_count' => fn ($query) => $query
                    ->where('is_active', true)
                    ->where('is_archived', false),
            ])
            ->orderByDesc('monthly_queue_count')
            ->orderByDesc('active_services_count')
            ->orderBy('nama_instansi')
            ->get();
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

}
