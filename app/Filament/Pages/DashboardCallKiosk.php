<?php

namespace App\Filament\Pages;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Services\QueueService;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View; // Penting untuk method render

class DashboardCallKiosk extends Page
{
    // --- Konfigurasi Halaman Filament ---
    // protected static ?string $navigationIcon = 'heroicon-o-speakerphone';
    protected static string $view = 'filament.pages.dashboard-call-kiosk';
    protected static ?string $title = 'Loket Panggilan Antrian';
    protected static ?string $navigationLabel = 'Loket Panggilan';
    protected static ?string $navigationIcon = 'heroicon-o-speaker-wave';


    // --- Properti untuk State Komponen ---
    public $counters; // Akan menampung semua loket untuk navigasi
    public ?int $selectedCounterId = null; // ID dari loket yang sedang dipilih


    /**
     * Method `mount` dijalankan sekali saat komponen pertama kali dimuat.
     * Kita gunakan untuk inisialisasi data awal.
     */
    public function mount(): void
    {
        // Ambil semua loket yang aktif untuk ditampilkan di navigasi
        $this->counters = Counter::with('service')->get();

        // Secara otomatis pilih loket pertama sebagai loket aktif saat halaman dibuka
        if ($this->counters->isNotEmpty()) {
            $this->selectedCounterId = $this->counters->first()->id;
        }
    }

    /**
     * Ini adalah Computed Property.
     * Cara elegan untuk mendapatkan model Counter yang sedang dipilih.
     * Bisa diakses di view dengan `$this->selectedCounter`.
     */
    public function getSelectedCounterProperty(): ?Counter
    {
        if (!$this->selectedCounterId) {
            return null;
        }
        return Counter::find($this->selectedCounterId);
    }

    // --- Aksi yang Dipanggil dari View ---

    /**
     * Method ini dipanggil saat pengguna mengklik loket lain di navigasi.
     * Ini adalah inti dari fitur "live selection".
     */
    public function selectCounter(int $counterId): void
    {
        $this->selectedCounterId = $counterId;
        // Livewire akan otomatis me-render ulang komponen dengan data baru
    }

    public function callNext(QueueService $queueService)
    {
        if (!$this->selectedCounter || !$this->selectedCounter->is_available) {
            return;
        }
        
        $nextQueue = $queueService->callNextQueue($this->selectedCounter->id);

        if ($nextQueue) {
            $nextQueue->update(['called_at' => null]);
        }
    }

    public function markAsServing(QueueService $queueService, Queue $queue)
    {
        $queueService->serveQueue($queue);
    }

    public function markAsFinished(QueueService $queueService, Queue $queue)
    {
        $queueService->finishQueue($queue);
    }

    public function markAsCancelled(QueueService $queueService, Queue $queue)
    {
        $queueService->cancelQueue($queue);
    }


    public function toggleCounterStatus()
    {
        if ($this->selectedCounter) {
            $this->selectedCounter->update([
                'is_active' => !$this->selectedCounter->is_active
            ]);
            // Refresh data loket di navigasi
            $this->counters = Counter::with('service')->get();
        }
    }


    public function getViewData(): array
    {
        $currentQueue = null;
        $waitingQueues = collect(); // Default ke koleksi kosong
        $stats = [
            'total' => 0, 'finished' => 0, 'waiting' => 0, 'cancelled' => 0
        ];

        // Hanya ambil data jika ada loket yang dipilih
        if ($this->selectedCounter) {
            $counter = $this->selectedCounter; // Ambil dari computed property

            $currentQueue = $counter->queues()
                ->whereIn('status', ['waiting','serving'])
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->first();

            $waitingQueues = Queue::where('service_id', $counter->service_id)
                ->whereIn('status', ['waiting'])
                ->where('called_at', null)
                ->whereDate('created_at', now()->format('Y-m-d'))
                ->get();

            // Kalkulasi statistik
            $baseQuery = Queue::where('service_id', $counter->service_id)->whereDate('created_at', now()->format('Y-m-d'));
            $stats['total'] = (clone $baseQuery)->count();
            $stats['finished'] = (clone $baseQuery)->where('status', 'finished')->count();
            $stats['waiting'] = $waitingQueues->count();
            $stats['cancelled'] = (clone $baseQuery)->where('status', 'canceled')->count();
        }

        // Kirim semua data yang dibutuhkan ke view
        return [
            'currentQueue' => $currentQueue,
            'waitingQueues' => $waitingQueues,
            'stats' => $stats,
        ];
    }
}