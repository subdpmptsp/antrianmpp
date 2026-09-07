<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\Instansi;
use App\Models\Queue;
use App\Models\Service;
use Illuminate\Support\Collection;

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

    /** @return Collection<int, Instansi> */
    public function rankedInstitutions(): Collection
    {
        return Instansi::query()
            ->where('is_active', true)
            ->where('is_archived', false)
            ->whereHas('counters', fn ($query) => $query
                ->where('is_active', true)
                ->where('is_archived', false)
                ->whereNotNull('service_id'))
            ->whereHas('services', fn ($query) => $query
                ->where('is_active', true)
                ->where('is_archived', false))
            ->orderBy('nama_instansi')
            ->get();
    }

    /**
     * Membentuk katalog tetap. Instansi yang ditambahkan di masa depan tetap
     * dapat diakses di bagian paling bawah, tanpa menggeser posisi utama.
     *
     * @return array<int, array{type: 'institution'|'bpjs', instansi_id?: int}>
     */
    public function institutionEntries(Collection $institutions): array
    {
        $configuredNames = collect(config('kiosk.institution_order', []));
        $bpjsNames = collect(config('kiosk.bpjs_institutions', []));
        $institutionsByName = $institutions->keyBy('nama_instansi');
        $usedIds = collect();
        $entries = [];

        foreach ($configuredNames as $name) {
            if ($name === 'bpjs') {
                if ($institutions->contains(fn (Instansi $institution): bool => $bpjsNames->contains($institution->nama_instansi))) {
                    $entries[] = ['type' => 'bpjs'];
                }

                continue;
            }

            $institution = $institutionsByName->get($name);
            if ($institution) {
                $entries[] = ['type' => 'institution', 'instansi_id' => $institution->instansi_id];
                $usedIds->push($institution->instansi_id);
            }
        }

        $remainingInstitutions = $institutions
            ->reject(fn (Instansi $institution): bool => $usedIds->contains($institution->instansi_id)
                || $bpjsNames->contains($institution->nama_instansi))
            ->sortBy('nama_instansi');

        foreach ($remainingInstitutions as $institution) {
            $entries[] = [
                'type' => 'institution',
                'instansi_id' => $institution->instansi_id,
            ];
        }

        return $entries;
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

        if ($services->isEmpty()) {
            return $services;
        }

        $counts = Queue::query()
            ->whereIn('service_id', $services->pluck('id'))
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
            $service->setAttribute('active_queue_count', (int) ($counts[$service->id] ?? 0));
            $service->setAttribute('is_recommended_consultation_counter', $isConsultationCounter
                && (int) ($counts[$service->id] ?? 0) === $minimumCount);

            return $service;
        });
    }

}
