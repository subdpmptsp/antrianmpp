<?php

namespace App\Filament\Pages;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Setting;
use Filament\Pages\Page;

class DashboardKiosk extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static string $view = 'filament.pages.dashboard-kiosk';

    protected static string $layout = 'filament.layouts.base-kiosk';

    protected static ?string $navigationLabel = 'Kiosk Ruang Tunggu';

    protected static ?string $navigationGroup = 'Display Kiosk';

    public static function canAccess(): bool
    {
        return auth()->user()->role === 'admin';
    }

    public function getViewData(): array
    {
        return [
            'counters' => Counter::with(['service', 'activeQueue'])->get(),
            'setting' => Setting::first()
        ];
    }

    public function callNextQueue()
    {
       $nextQueues = Queue::where('status', 'waiting')
       ->whereDate('created_at', now()->format('Y-m-d'))
       ->whereNull('called_at')
       ->get();
       
       foreach ($nextQueues as $nextQueue)
       {
            if (!$nextQueue->counter) continue;

            $spokenNumber = preg_replace('/([A-Za-z])(\d+)/', '$1-$2', $nextQueue->number);

            $this->dispatch("queue-called", "Nomor antrian $spokenNumber dipersilakan ke " . $nextQueue->counter->name);

            $nextQueue->update(['called_at' => now()]);
       }
    }

}
