<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageServices extends ManageRecords
{
    protected static string $resource = ServiceResource::class;

    public function getSubheading(): ?string
    {
        return 'Halaman ini dipakai untuk mengatur layanan antrian. Nama layanan tampil di sistem, Prefix dipakai sebagai awalan nomor antrian, dan sistem memakai digit default secara otomatis.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
