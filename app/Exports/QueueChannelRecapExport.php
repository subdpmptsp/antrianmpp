<?php

namespace App\Exports;

use App\Models\OnlineQueueReservation;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class QueueChannelRecapExport implements FromCollection, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        private readonly string $from,
        private readonly string $to,
        private readonly ?int $serviceId = null,
    ) {}

    public function collection(): Collection
    {
        $from = Carbon::parse($this->from, 'Asia/Jakarta')->startOfDay();
        $to = Carbon::parse($this->to, 'Asia/Jakarta')->endOfDay();

        $queueCounts = DB::table('queues')
            ->join('services', 'services.id', '=', 'queues.service_id')
            ->leftJoin('instansis', 'instansis.instansi_id', '=', 'services.instansi_id')
            ->whereBetween('queues.created_at', [$from, $to])
            ->when($this->serviceId, fn ($query) => $query->where('queues.service_id', $this->serviceId))
            ->selectRaw("DATE(queues.created_at) as report_date, queues.service_id, services.name as service_name, services.prefix, instansis.nama_instansi as institution_name, CASE WHEN queues.source = 'online' THEN 'online' ELSE 'onsite' END as channel, COUNT(*) as total")
            ->groupBy('report_date', 'queues.service_id', 'services.name', 'services.prefix', 'instansis.nama_instansi', 'channel')
            ->get();

        $reservationCounts = OnlineQueueReservation::query()
            ->join('services', 'services.id', '=', 'online_queue_reservations.service_id')
            ->leftJoin('instansis', 'instansis.instansi_id', '=', 'services.instansi_id')
            ->whereBetween('online_queue_reservations.service_date', [$from->toDateString(), $to->toDateString()])
            ->when($this->serviceId, fn ($query) => $query->where('online_queue_reservations.service_id', $this->serviceId))
            ->whereIn('online_queue_reservations.status', [
                OnlineQueueReservation::STATUS_CANCELED,
                OnlineQueueReservation::STATUS_EXPIRED,
            ])
            ->selectRaw('online_queue_reservations.service_date as report_date, online_queue_reservations.service_id, services.name as service_name, services.prefix, instansis.nama_instansi as institution_name, online_queue_reservations.status, COUNT(*) as total')
            ->groupBy('report_date', 'online_queue_reservations.service_id', 'services.name', 'services.prefix', 'instansis.nama_instansi', 'online_queue_reservations.status')
            ->get();

        $rows = [];
        foreach ($queueCounts as $count) {
            $key = $count->report_date.'|'.$count->service_id;
            $rows[$key] ??= $this->emptyRow($count);
            $rows[$key][$count->channel] = (int) $count->total;
        }
        foreach ($reservationCounts as $count) {
            $key = $count->report_date.'|'.$count->service_id;
            $rows[$key] ??= $this->emptyRow($count);
            $rows[$key][$count->status === OnlineQueueReservation::STATUS_CANCELED ? 'canceled' : 'no_show'] = (int) $count->total;
        }

        return collect($rows)
            ->map(function (array $row): array {
                $row['total'] = $row['onsite'] + $row['online'];

                return [
                    Carbon::parse($row['date'])->format('d-m-Y'),
                    $row['institution'],
                    $row['prefix'],
                    $row['service'],
                    $row['onsite'],
                    $row['online'],
                    $row['total'],
                    $row['canceled'],
                    $row['no_show'],
                ];
            })
            ->sortBy(fn (array $row) => $row[0].'|'.$row[1].'|'.$row[2])
            ->values();
    }

    public function headings(): array
    {
        return ['Tanggal', 'Instansi', 'Prefix Layanan', 'Layanan', 'Antrean Langsung', 'Antrean Online Hadir', 'Total Nomor Terbit', 'Pembatalan Online', 'Tidak Hadir (No-show)'];
    }

    public function title(): string
    {
        return 'Rekap Antrean Langsung dan Online';
    }

    /** @return array{date:string,institution:string,prefix:string,service:string,onsite:int,online:int,canceled:int,no_show:int} */
    private function emptyRow(object $count): array
    {
        return [
            'date' => $count->report_date,
            'institution' => $count->institution_name ?: '-',
            'prefix' => $count->prefix,
            'service' => $count->service_name,
            'onsite' => 0,
            'online' => 0,
            'canceled' => 0,
            'no_show' => 0,
        ];
    }
}
