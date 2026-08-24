<?php

namespace App\Exports;

use App\Models\Instansi;
use App\Services\AttendanceReportService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendanceYearlyRecapExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping, WithTitle
{
    protected $year;

    public function __construct($year = null)
    {
        $this->year = $year ?: Carbon::now()->year;
    }

    public function collection(): Collection
    {
        $recap = app(AttendanceReportService::class)->monthlyRecap($this->year);

        return $recap['instansis']->values()->map(function (array $instansi, int $index): array {
            $row = [
                'no' => $index + 1,
                'nama_instansi' => $instansi['nama_instansi'],
            ];

            for ($month = 1; $month <= 12; $month++) {
                $row['bulan_'.$month] = $instansi['months'][$month]['percentage'] ?? null;
            }

            return $row;
        });
    }

    public function headings(): array
    {
        $monthNames = [
            1 => 'Januari',
            2 => 'Februari',
            3 => 'Maret',
            4 => 'April',
            5 => 'Mei',
            6 => 'Juni',
            7 => 'Juli',
            8 => 'Agustus',
            9 => 'September',
            10 => 'Oktober',
            11 => 'November',
            12 => 'Desember',
        ];

        $headings = ['No.', 'Nama Instansi'];

        foreach ($monthNames as $monthName) {
            $headings[] = $monthName;
        }

        return $headings;
    }

    public function map($row): array
    {
        $mapped = [
            $row['no'],
            $row['nama_instansi'],
        ];

        for ($month = 1; $month <= 12; $month++) {
            $percentage = $row['bulan_'.$month] ?? null;
            $mapped[] = $percentage === null ? '-' : number_format($percentage, 0).'%';
        }

        return $mapped;
    }

    public function title(): string
    {
        return 'Rekap Tahun '.$this->year;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Get the last row and column
                $lastRow = $sheet->getHighestRow();
                $lastColumn = Coordinate::stringFromColumnIndex(14); // No, Instansi, 12 bulan = 14 columns

                // Insert 2 rows at the top for title
                $sheet->insertNewRowBefore(1, 2);

                // After insertion, header is now at row 3, data starts at row 4
                $headerRow = 3;
                $dataStartRow = 4;
                $lastRow = $lastRow + 2; // Adjust for inserted rows

                // Add title
                $sheet->setCellValue('A1', 'Rekapitulasi Absensi Petugas Tahun '.$this->year);
                $sheet->mergeCells('A1:'.$lastColumn.'1');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
                $sheet->getStyle('A1')->getAlignment()->setVertical('center');

                // Add note
                $sheet->setCellValue('A2', 'NB: Kehadiran dalam % tiap bulan');
                $sheet->mergeCells('A2:'.$lastColumn.'2');
                $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

                // Style header row (row 3)
                $headerRange = 'A'.$headerRow.':'.$lastColumn.$headerRow;
                $sheet->getStyle($headerRange)->getFont()->setBold(true);
                $sheet->getStyle($headerRange)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFF3F4F6'); // Light gray background
                $sheet->getStyle($headerRange)->getFont()->getColor()
                    ->setARGB('FF1F2937'); // Dark gray text
                $sheet->getStyle($headerRange)->getAlignment()->setHorizontal('center');
                $sheet->getStyle($headerRange)->getAlignment()->setVertical('center');

                // Add borders to all cells
                $dataRange = 'A'.$headerRow.':'.$lastColumn.$lastRow;
                $sheet->getStyle($dataRange)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Center align No column and month columns
                if ($lastRow >= $dataStartRow) {
                    $sheet->getStyle('A'.$dataStartRow.':A'.$lastRow)->getAlignment()->setHorizontal('center'); // No
                    // Center align all month columns (C to N)
                    for ($col = 3; $col <= 14; $col++) {
                        $colLetter = Coordinate::stringFromColumnIndex($col);
                        $sheet->getStyle($colLetter.$dataStartRow.':'.$colLetter.$lastRow)->getAlignment()->setHorizontal('center');
                    }
                }

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(6); // No
                $sheet->getColumnDimension('B')->setWidth(50); // Nama Instansi
                for ($col = 3; $col <= 14; $col++) {
                    $colLetter = Coordinate::stringFromColumnIndex($col);
                    $sheet->getColumnDimension($colLetter)->setWidth(12); // Month columns
                }

                // Color code cells based on percentage (conditional formatting)
                for ($row = $dataStartRow; $row <= $lastRow; $row++) {
                    for ($col = 3; $col <= 14; $col++) {
                        $colLetter = Coordinate::stringFromColumnIndex($col);
                        $cell = $colLetter.$row;
                        $cellValue = $sheet->getCell($cell)->getValue();

                        // Extract percentage value
                        if (preg_match('/(\d+(?:\.\d+)?)/', $cellValue, $matches)) {
                            $percentage = (float) $matches[1];

                            // Warna berdasarkan persentase: 100% = hijau, <100% = merah
                            if ($percentage >= 100) {
                                // Sudah 100% - warna hijau
                                $sheet->getStyle($cell)->getFill()
                                    ->setFillType(Fill::FILL_SOLID)
                                    ->getStartColor()->setARGB('FFC6EFCE'); // Light green
                                $sheet->getStyle($cell)->getFont()->getColor()
                                    ->setARGB('FF006100'); // Dark green
                            } else {
                                // Belum 100% - warna merah
                                $sheet->getStyle($cell)->getFill()
                                    ->setFillType(Fill::FILL_SOLID)
                                    ->getStartColor()->setARGB('FFFFC7CE'); // Light red
                                $sheet->getStyle($cell)->getFont()->getColor()
                                    ->setARGB('FF9C0006'); // Dark red
                            }

                            $sheet->getStyle($cell)->getFont()->setBold(true);
                        }
                    }
                }

                // Freeze first row and first 2 columns
                $sheet->freezePane('C'.$headerRow);
            },
        ];
    }
}
