<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class RekapLayananExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithTitle
{
    /** @var array<int, int> */
    private array $institutionHeaderRows = [];

    public function __construct(
        protected string $from,
        protected string $to,
        protected ?string $zoneId = null,
    ) {}

    public function headings(): array
    {
        return array_merge(['No.', 'Instansi / Layanan'], range(1, 31));
    }

    public function collection(): Collection
    {
        $from = Carbon::parse($this->from)->startOfDay();
        $to = Carbon::parse($this->to)->endOfDay();
        $dates = collect(range(1, 31));

        $services = DB::table('services as s')
            ->join('instansis as i', 'i.instansi_id', '=', 's.instansi_id')
            ->where('s.is_active', true)
            ->where('s.is_archived', false)
            ->when($this->zoneId && $this->zoneId !== 'all', function ($query): void {
                $zoneName = (string) config("tv.zones.{$this->zoneId}.name", "ZONA {$this->zoneId}");
                $query->where('i.zone', $zoneName);
            })
            ->select(['s.id', 's.prefix', 's.name as service_name', 'i.instansi_id', 'i.nama_instansi'])
            ->orderBy('i.nama_instansi')
            ->orderBy('s.prefix')
            ->get();

        $dailyCounts = DB::table('queues')
            ->whereIn('service_id', $services->pluck('id'))
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('service_id, DATE(created_at) as queue_date, COUNT(*) as total')
            ->groupBy('service_id', 'queue_date')
            ->get()
            ->groupBy('service_id')
            ->map(fn (Collection $counts) => $counts->pluck('total', 'queue_date'));

        $rows = collect();
        $rowIndex = 0;

        foreach ($services->groupBy('instansi_id') as $institutionServices) {
            $institution = $institutionServices->first();
            $institutionTotals = $dates->map(function (int $day) use ($institutionServices, $dailyCounts, $from): int {
                $date = $from->copy()->day($day)->toDateString();

                return (int) $institutionServices->sum(fn ($service) => (int) ($dailyCounts->get($service->id)?->get($date, 0) ?? 0));
            });

            $rows->push(array_merge(['', $institution->nama_instansi], $institutionTotals->all()));
            $this->institutionHeaderRows[] = 5 + $rowIndex;
            $rowIndex++;

            foreach ($institutionServices as $service) {
                $serviceTotals = $dates->map(function (int $day) use ($dailyCounts, $service, $from): int {
                    $date = $from->copy()->day($day)->toDateString();

                    return (int) ($dailyCounts->get($service->id)?->get($date, 0) ?? 0);
                });

                $rows->push(array_merge(['', '↳ '.$service->prefix.' — '.$service->service_name], $serviceTotals->all()));
                $rowIndex++;
            }
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Rekap Layanan';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $from = Carbon::parse($this->from)->startOfDay();
                $lastColumn = Coordinate::stringFromColumnIndex(33);

                $sheet->insertNewRowBefore(1, 4);
                $sheet->removeRow(5, 1);

                $sheet->setCellValue('A1', 'Rekap Jumlah Pemohon Mall Pelayanan Publik Kota Surabaya');
                $sheet->mergeCells("A1:{$lastColumn}1");
                $sheet->setCellValue('A2', 'Periode '.$from->format('d F Y').' s.d. '.Carbon::parse($this->to)->format('d F Y'));
                $sheet->mergeCells("A2:{$lastColumn}2");
                $sheet->setCellValue('A3', 'No.');
                $sheet->mergeCells('A3:A4');
                $sheet->setCellValue('B3', 'Instansi / Layanan');
                $sheet->mergeCells('B3:B4');
                $sheet->setCellValue('C3', 'Tanggal');
                $sheet->mergeCells("C3:{$lastColumn}3");

                foreach (range(1, 31) as $day) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($day + 2).'4', $day);
                }

                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(11);
                $sheet->getStyle("A1:{$lastColumn}2")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("A3:{$lastColumn}4")->getFont()->setBold(true);
                $sheet->getStyle("A3:{$lastColumn}4")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDCE6F1');
                $sheet->getStyle("A3:{$lastColumn}4")->getAlignment()->setHorizontal('center')->setVertical('center');

                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A3:{$lastColumn}{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $sheet->getStyle("A5:A{$lastRow}")->getAlignment()->setHorizontal('center');
                $sheet->getStyle("C5:{$lastColumn}{$lastRow}")->getAlignment()->setHorizontal('center');
                $sheet->getColumnDimension('A')->setWidth(8);
                $sheet->getColumnDimension('B')->setWidth(58);

                foreach (range(3, 33) as $column) {
                    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($column))->setWidth(6);
                }

                foreach ($this->institutionHeaderRows as $row) {
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFont()->setBold(true);
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD9EAF7');
                }
            },
        ];
    }
}
