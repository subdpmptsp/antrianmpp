<?php

namespace App\Filament\Resources\InstansiResource\Pages;

use App\Filament\Resources\InstansiResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ListInstansis extends ManageRecords
{
    protected static string $resource = InstansiResource::class;

    public function getSubheading(): ?string
    {
        return 'Langkah 1 dari 4. Buat instansi dan tentukan zonanya sebelum menambahkan layanan.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Instansi')
                ->modalHeading('Tambah Instansi Baru')
                ->modalSubmitActionLabel('Simpan Instansi')
                ->modalCancelActionLabel('Batal')
                ->modalWidth('3xl'),
        ];
    }
}
