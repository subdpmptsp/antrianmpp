<?php

namespace App\Filament\Resources\CounterResource\Pages;

use App\Filament\Resources\CounterResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCounters extends ManageRecords
{
    protected static string $resource = CounterResource::class;

    public function getSubheading(): ?string
    {
        return 'Langkah 3 dari 4. Pilih instansi dan layanan utama. Zona loket otomatis mengikuti zona instansi.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Tambah Loket'),
        ];
    }
}
