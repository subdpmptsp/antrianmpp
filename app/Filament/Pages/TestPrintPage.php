<?php

namespace App\Filament\Pages;

use App\Models\Service;
use Filament\Pages\Page;

class TestPrintPage extends Page
{
    protected static string $view = 'filament.pages.test-print';

    protected static ?string $title = 'Test Print';

    protected static ?string $navigationLabel = 'Test Print';

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?string $navigationIcon = 'heroicon-o-printer';

    public static function canAccess(): bool
    {
        return (auth()->user()?->can('access-admin-area') ?? false)
            && app()->environment(['local', 'testing']);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function testPrint()
    {
        $serviceId = $this->previewServiceId();

        if (! $serviceId) {
            $this->dispatch('notify', type: 'warning', message: 'Belum ada layanan aktif untuk diuji.');

            return;
        }

        $this->dispatch('open-pdf', url: route('struk.preview', ['service_id' => $serviceId]));
    }

    protected function getViewData(): array
    {
        $serviceId = $this->previewServiceId();

        return [
            'previewUrl' => $serviceId ? route('struk.preview', ['service_id' => $serviceId]) : null,
        ];
    }

    private function previewServiceId(): ?int
    {
        $serviceId = Service::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->value('id');

        return $serviceId ? (int) $serviceId : null;
    }
}
