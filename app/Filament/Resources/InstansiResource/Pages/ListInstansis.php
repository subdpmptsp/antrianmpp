<?php

namespace App\Filament\Resources\InstansiResource\Pages;

use App\Filament\Resources\InstansiResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ListInstansis extends ManageRecords
{
    protected static string $resource = InstansiResource::class;

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
