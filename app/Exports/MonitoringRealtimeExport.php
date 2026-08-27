<?php

namespace App\Exports;

use App\Services\MonitoringRealtimeService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MonitoringRealtimeExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        protected ?string $zoneId = null,
        protected ?string $search = null,
    ) {}

    public function collection(): Collection
    {
        $services = app(MonitoringRealtimeService::class)
            ->getServices($this->zoneId, $this->search);

        return $services->map(fn ($service) => [
            'Layanan' => $service->name,
            'Instansi' => $service->instansi?->nama_instansi ?? '-',
            'Menunggu' => $service->menunggu_count,
            'Dipanggil' => $service->dipanggil_count,
            'Dilayani' => $service->dilayani_count,
            'Selesai' => $service->selesai_count,
            'Batal' => $service->batal_count,
        ]);
    }

    public function headings(): array
    {
        return ['Layanan', 'Instansi', 'Menunggu', 'Dipanggil', 'Dilayani', 'Selesai', 'Batal'];
    }

    public function title(): string
    {
        return 'Monitoring Real-Time';
    }
}
