<?php

namespace App\Exports;

use App\Models\OnlineQueueReservation;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OnlineQueueReservationsExport implements FromQuery, WithColumnFormatting, WithColumnWidths, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(
        private readonly ?string $date = null,
        private readonly ?string $status = null,
        private readonly ?int $serviceId = null,
        private readonly ?string $search = null,
    ) {}

    public function query(): Builder
    {
        return OnlineQueueReservation::query()
            ->with(['service.instansi', 'session', 'queue'])
            ->when($this->date, fn (Builder $query) => $query->whereDate('service_date', $this->date))
            ->when($this->status && $this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->serviceId, fn (Builder $query) => $query->where('service_id', $this->serviceId))
            ->when($this->search, function (Builder $query): void {
                $term = trim((string) $this->search);

                $query->where(fn (Builder $nested) => $nested
                    ->where('booking_code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%"));
            })
            ->latest();
    }

    /** @return array<int, float|int|string|null> */
    public function map($reservation): array
    {
        $session = $reservation->session;
        $sessionLabel = $session
            ? substr((string) $session->starts_at, 0, 5).'–'.substr((string) $session->ends_at, 0, 5)
            : '-';

        return [
            $reservation->booking_code,
            Date::dateTimeToExcel($reservation->service_date),
            $reservation->name,
            $reservation->masked_nik,
            $reservation->phone,
            $reservation->service?->instansi?->nama_instansi ?? '-',
            $reservation->service?->name ?? '-',
            $sessionLabel,
            $this->statusLabel((string) $reservation->status),
            $reservation->queue?->number ?? '-',
            $reservation->checked_in_at ? Date::dateTimeToExcel($reservation->checked_in_at) : null,
        ];
    }

    public function headings(): array
    {
        return [
            'Kode Booking',
            'Tanggal Layanan',
            'Nama Pemohon',
            'NIK Tersamarkan',
            'Nomor WhatsApp',
            'Instansi',
            'Layanan',
            'Sesi',
            'Status',
            'Nomor Antrean',
            'Waktu Check-in',
        ];
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_TEXT,
            'B' => 'dd-mm-yyyy',
            'D' => NumberFormat::FORMAT_TEXT,
            'E' => NumberFormat::FORMAT_TEXT,
            'J' => NumberFormat::FORMAT_TEXT,
            'K' => 'dd-mm-yyyy hh:mm',
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 22,
            'B' => 17,
            'C' => 25,
            'D' => 20,
            'E' => 19,
            'F' => 34,
            'G' => 44,
            'H' => 17,
            'I' => 27,
            'J' => 19,
            'K' => 21,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF174D9B'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(1, $sheet->getHighestDataRow());

                $sheet->freezePane('A2');
                $sheet->setAutoFilter("A1:K{$lastRow}");
                $sheet->getRowDimension(1)->setRowHeight(28);
                $sheet->getStyle("A1:K{$lastRow}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle("C2:C{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("F2:I{$lastRow}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("A1:K{$lastRow}")->getBorders()->getHorizontal()
                    ->setBorderStyle(Border::BORDER_HAIR)
                    ->getColor()->setARGB('FFD9E2EF');
                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
            },
        ];
    }

    public function title(): string
    {
        return 'Pendaftar Antrean Online';
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'booked' => 'Belum hadir / check-in',
            'checked_in' => 'Hadir / sudah check-in',
            'canceled' => 'Dibatalkan',
            'expired' => 'Tidak hadir (no-show)',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
