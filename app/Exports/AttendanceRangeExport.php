<?php

namespace App\Exports;

use App\Services\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AttendanceRangeExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(
        private readonly string $from,
        private readonly string $until,
        private readonly string $search = '',
        private readonly ?int $instansiId = null,
        private readonly string $status = 'all',
    ) {}

    public function collection(): Collection
    {
        return app(AttendanceReportService::class)->historyRows(
            Carbon::parse($this->from),
            Carbon::parse($this->until),
            $this->search,
            $this->instansiId,
            $this->status,
        );
    }

    public function headings(): array
    {
        return ['Tanggal', 'Nama Petugas', 'Instansi', 'Status', 'Jam Login'];
    }

    public function map($row): array
    {
        return [
            Carbon::parse($row['date'])->format('d-m-Y'),
            $row['name'],
            $row['instansi'],
            match ($row['status']) {
                'present' => 'Hadir',
                'absent' => 'Belum/Tidak Hadir',
                'unassigned' => 'Instansi Belum Diatur',
                default => ucfirst($row['status']),
            },
            $row['check_in'] ?? '-',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
