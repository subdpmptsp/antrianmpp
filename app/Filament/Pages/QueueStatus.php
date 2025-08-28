<?php

namespace App\Filament\Pages;

use App\Models\Queue;
use Filament\Pages\BasePage;
use Livewire\Attributes\Url;

class QueueStatus extends BasePage
{
    protected static string $view = 'filament.pages.queue-status';

    protected static string $layout = 'filament.layouts.base-status';

    protected static ?string $title = 'Status Antrian';

    #[Url]
    public ?string $id = null;

    public $queue = null;

    public $waitingCount = 0;

    public $currentQueues = [];

    public $statusLabels = [
        'waiting' => 'Menunggu',
        'called' => 'Dipanggil',
        'serving' => 'Dilayani',
        'finished' => 'Selesai',
        'canceled' => 'Dibatalkan'
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        if (!$this->id) return;

        $id = decode_id($this->id);

        $this->queue = Queue::findOrFail($id);

        $this->waitingCount = Queue::where('status', 'waiting')
            ->where('service_id', $this->queue->service_id)
            ->where('created_at', '<', $this->queue->created_at)
            ->whereDate('created_at', $this->queue->created_at->format('Y-m-d'))
            ->count();

        $this->currentQueues = Queue::where('status', 'serving')
            ->where('service_id', $this->queue->service_id)
            ->get();
    }

    public function getStatusLabel()
    {
        if (!$this->queue) return '';

        return $this->statusLabels[$this->queue->status] ?? '';
    }
}
