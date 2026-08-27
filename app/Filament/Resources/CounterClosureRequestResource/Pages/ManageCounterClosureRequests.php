<?php

namespace App\Filament\Resources\CounterClosureRequestResource\Pages;

use App\Filament\Resources\CounterClosureRequestResource;
use Filament\Resources\Pages\ManageRecords;

class ManageCounterClosureRequests extends ManageRecords
{
    protected static string $resource = CounterClosureRequestResource::class;

    public function getSubheading(): ?string
    {
        return 'Tinjau pengajuan penutupan loket. Persetujuan menutup pengambilan nomor baru dari kiosk, tetapi petugas tetap dapat menyelesaikan antrean yang sudah ada.';
    }
}
