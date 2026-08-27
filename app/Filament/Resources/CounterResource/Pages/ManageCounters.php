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
        return 'Halaman ini digunakan untuk mengatur loket fisik tempat petugas memanggil antrean. Tentukan zona, instansi, kode loket, layanan yang ditangani, dan status aktifnya.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
