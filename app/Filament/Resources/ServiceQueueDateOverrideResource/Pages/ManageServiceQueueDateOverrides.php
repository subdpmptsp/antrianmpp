<?php

namespace App\Filament\Resources\ServiceQueueDateOverrideResource\Pages;

use App\Filament\Resources\ServiceQueueDateOverrideResource;
use Filament\Resources\Pages\ManageRecords;

class ManageServiceQueueDateOverrides extends ManageRecords
{
    protected static string $resource = ServiceQueueDateOverrideResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
