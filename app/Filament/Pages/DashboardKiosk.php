<?php

namespace App\Filament\Pages;

use App\Models\Counter;
use App\Models\Setting;
use Filament\Pages\Page;

class DashboardKiosk extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static string $view = 'filament.pages.dashboard-kiosk';

    protected static string $layout = 'filament.layouts.base-kiosk';

    protected static ?string $navigationLabel = 'Kiosk Ruang Tunggu';

    protected static ?string $navigationGroup = 'Operasional';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('access-admin-area') ?? false;
    }

    public function getViewData(): array
    {
        // Refresh counters dengan relasi lengkap untuk sinkronisasi real-time
        // Pastikan semua counter ditampilkan termasuk ZONA 4 dan ZONA 5
        $counters = Counter::query()
            ->withoutGlobalScopes()
            ->with([
                'service', 
                'activeQueue.service', 
                'nextQueue.service',
                'queues' => function($query) {
                    $query->whereIn('status', ['called', 'serving'])
                        ->whereDate('created_at', now()->toDateString())
                        ->orderByRaw("CASE WHEN status = 'serving' THEN 1 WHEN status = 'called' THEN 2 END")
                        ->latest('called_at')
                        ->limit(1);
                }
            ])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return [
            'counters' => $counters,
            'setting' => Setting::first() ?? (object)[
                'name' => 'Mall Pelayanan Publik',
                'address' => 'Alamat belum diatur',
                'image' => null,
            ],
        ];
    }

    /**
     * Method untuk refresh data real-time
     * Dipanggil oleh wire:poll untuk sinkronisasi dengan DashboardCallKiosk
     */
    public function refreshData()
    {
        // Hanya refresh data, tidak perlu logic tambahan
        // Livewire akan otomatis re-render dengan data terbaru
    }
    
    /**
     * Method legacy untuk backward compatibility
     * Sekarang hanya memanggil refreshData
     */
    public function callNextQueue()
    {
        // Method ini dipanggil oleh wire:poll
        // Sekarang hanya refresh data untuk sinkronisasi
        $this->refreshData();
    }
}
