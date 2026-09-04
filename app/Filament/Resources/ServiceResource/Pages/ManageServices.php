<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use App\Models\Instansi;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageServices extends ManageRecords
{
    protected static string $resource = ServiceResource::class;

    public function getSubheading(): ?string
    {
        if (! Instansi::query()->where('is_active', true)->where('is_archived', false)->exists()) {
            return 'Belum ada instansi aktif. Buka menu 1. Instansi & Zona terlebih dahulu sebelum membuat layanan.';
        }

        return 'Langkah 2 dari 4. Setiap layanan wajib dimiliki satu instansi; zona otomatis mengikuti instansi tersebut.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Layanan')
                ->disabled(fn (): bool => ! Instansi::query()->where('is_active', true)->where('is_archived', false)->exists())
                ->tooltip(fn (): ?string => Instansi::query()->where('is_active', true)->where('is_archived', false)->exists()
                    ? null
                    : 'Tambahkan instansi aktif terlebih dahulu.'),
        ];
    }
}
