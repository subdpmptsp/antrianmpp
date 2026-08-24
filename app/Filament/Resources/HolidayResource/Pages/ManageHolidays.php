<?php

namespace App\Filament\Resources\HolidayResource\Pages;

use App\Filament\Resources\HolidayResource;
use App\Models\Holiday;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ManageHolidays extends ManageRecords
{
    protected static string $resource = HolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('importCsv')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('File kalender hari libur')
                        ->helperText('Kolom: tanggal,nama,jenis,catatan. Jenis: national, collective, atau local.')
                        ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel'])
                        ->storeFiles(false)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $file = $data['file'];

                    if (! $file instanceof UploadedFile) {
                        Notification::make()->title('File tidak dapat dibaca')->danger()->send();

                        return;
                    }

                    $handle = fopen($file->getRealPath(), 'rb');
                    $imported = 0;

                    DB::transaction(function () use ($handle, &$imported): void {
                        $firstLine = fgets($handle);
                        if ($firstLine === false) {
                            return;
                        }

                        $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
                        rewind($handle);
                        $header = array_map(fn (string $value): string => strtolower(trim($value)), fgetcsv($handle, 0, $delimiter));
                        $map = array_flip($header);

                        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                            $date = trim($row[$map['tanggal'] ?? $map['date'] ?? -1] ?? '');
                            $name = trim($row[$map['nama'] ?? $map['name'] ?? -1] ?? '');

                            if ($date === '' || $name === '') {
                                continue;
                            }

                            $type = trim($row[$map['jenis'] ?? $map['type'] ?? -1] ?? 'national');
                            if (! in_array($type, ['national', 'collective', 'local'], true)) {
                                $type = 'national';
                            }

                            Holiday::updateOrCreate(
                                ['date' => Carbon::parse($date)->toDateString()],
                                [
                                    'name' => $name,
                                    'type' => $type,
                                    'notes' => trim($row[$map['catatan'] ?? $map['notes'] ?? -1] ?? '') ?: null,
                                ],
                            );
                            $imported++;
                        }
                    });

                    fclose($handle);
                    Notification::make()->title("{$imported} hari libur berhasil diimpor")->success()->send();
                }),
        ];
    }
}
