<?php

namespace App\Http\Controllers;

use App\Models\Counter;
use App\Models\Queue;
use App\Models\Service;
use App\Services\AnnouncementService;
use App\Services\KioskCatalogService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TvDisplayController extends Controller
{
    public function index(KioskCatalogService $catalog): View
    {
        return view('tv-landing', [
            'zones' => $this->zoneCards($catalog, true),
        ]);
    }

    public function landing(KioskCatalogService $catalog): View
    {
        return view('tv-landing', [
            'zones' => $this->zoneCards($catalog, true),
        ]);
    }

    public function getQueueStatus()
    {
        $today = Carbon::today();

        // Get all services with their status
        $services = Service::where('is_active', true)
            ->with(['counters' => function ($query) {
                $query->where('is_active', true);
            }, 'queues' => function ($query) use ($today) {
                $query->whereDate('created_at', $today)
                    ->whereIn('status', ['waiting', 'called', 'serving'])
                    ->with('counter')
                    ->orderByRaw("CASE WHEN status = 'serving' THEN 0 WHEN status = 'called' THEN 1 ELSE 2 END")
                    ->orderByDesc('called_at')
                    ->orderBy('created_at');
            }])
            ->get()
            ->map(function ($service) {
                $activeCounters = $service->counters->count();
                $nextQueue = $service->queues
                    ->first(fn ($queue) => $queue->status === 'waiting' && $queue->called_at === null);
                $servingQueue = $service->queues
                    ->first(fn ($queue) => in_array($queue->status, ['called', 'serving'], true));

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'prefix' => $service->prefix,
                    'nextQueue' => $nextQueue ? $nextQueue->number : null,
                    'servingQueue' => $servingQueue ? [
                        'number' => $servingQueue->number,
                        'counter' => $servingQueue->counter ? $servingQueue->counter->name : null,
                    ] : null,
                    'activeCounters' => $activeCounters,
                    'totalCounters' => $activeCounters,
                    'status' => $servingQueue ? 'serving' : ($nextQueue ? 'waiting' : 'available'),
                ];
            });

        // Get current serving queues for display
        $servingQueues = Queue::whereIn('status', ['called', 'serving'])
            ->whereDate('created_at', $today)
            ->with(['service', 'counter'])
            ->orderBy('called_at', 'desc')
            ->limit(3)
            ->get()
            ->map(function ($queue) {
                return [
                    'number' => $queue->number,
                    'service' => $queue->service ? $queue->service->name : 'Layanan',
                    'counter' => $queue->counter ? $queue->counter->name : 'Loket',
                ];
            });

        // Get available counters
        $availableCounters = Counter::where('is_active', true)
            ->whereDoesntHave('queues', function ($query) use ($today) {
                $query->whereIn('status', ['called', 'serving'])
                    ->whereDate('created_at', $today);
            })
            ->with('service')
            ->limit(3)
            ->get()
            ->map(function ($counter) {
                return [
                    'name' => $counter->name,
                    'service' => $counter->service ? $counter->service->name : 'Layanan',
                ];
            });

        return response()->json([
            'services' => $services,
            'servingQueues' => $servingQueues,
            'availableCounters' => $availableCounters,
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function getLatestAnnouncement(Request $request, AnnouncementService $announcements)
    {
        return response()->json(
            $announcements->latest(
                $request->string('after_id')->toString() ?: null,
                $request->integer('zone_id') ?: null,
            ),
        );
    }

    // Get services data for specific zone
    public function getZoneServices($zoneId)
    {
        $today = Carbon::today();

        // Get zone counter
        $zoneCounter = Counter::where('id', $zoneId)->first();
        if (! $zoneCounter) {
            return response()->json(['error' => 'Zone not found'], 404);
        }

        // Get services for this zone
        $services = Service::whereHas('instansi', function ($query) use ($zoneId) {
            $query->where('counter_id', $zoneId);
        })
            ->where('is_active', true)
            ->with(['instansi'])
            ->get()
            ->map(function ($service) use ($today) {
                // Get next queue for this service
                $nextQueue = Queue::where('service_id', $service->id)
                    ->where('status', 'waiting')
                    ->where('called_at', null)
                    ->whereDate('created_at', $today)
                    ->orderBy('created_at')
                    ->first();

                // Get current serving queue
                $servingQueue = Queue::where('service_id', $service->id)
                    ->where('status', 'serving')
                    ->whereDate('created_at', $today)
                    ->with('counter')
                    ->first();

                // Count active counters for this service
                $activeCounters = Counter::where('service_id', $service->id)
                    ->where('is_active', true)
                    ->count();

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'prefix' => $service->prefix,
                    'next_queue' => $nextQueue ? $nextQueue->number : null,
                    'active_counters' => $activeCounters,
                    'total_counters' => $activeCounters,
                    'status' => $servingQueue ? 'serving' : ($nextQueue ? 'waiting' : 'available'),
                ];
            });

        return response()->json([
            'zone_name' => $zoneCounter->name,
            'services' => $services,
            'timestamp' => now()->toISOString(),
        ]);
    }

    // Get queues data for specific zone
    public function getZoneQueues($zoneId)
    {
        $today = Carbon::today();

        // Get zone counter
        $zoneCounter = Counter::where('id', $zoneId)->first();
        if (! $zoneCounter) {
            return response()->json(['error' => 'Zone not found'], 404);
        }

        // Get all counters for this zone
        $zoneCounters = Counter::where(function ($query) use ($zoneId, $zoneCounter) {
            $query->where('id', $zoneId)
                ->orWhere('name', 'like', $zoneCounter->name.'%');
        })
            ->where('is_active', true)
            ->get();

        $counterIds = $zoneCounters->pluck('id');

        $currentQueues = Queue::whereIn('counter_id', $counterIds)
            ->whereIn('status', ['serving', 'called'])
            ->whereDate('created_at', $today)
            ->with('service')
            ->orderByRaw("CASE WHEN status = 'serving' THEN 1 WHEN status = 'called' THEN 2 END")
            ->orderByDesc('called_at')
            ->get()
            ->groupBy('counter_id');

        $queues = $zoneCounters->map(function ($counter) use ($currentQueues) {
            $currentQueue = $currentQueues->get($counter->id)?->first();

            $queueData = [
                'counter_id' => $counter->id,
                'counter_name' => $counter->name,
                'status' => 'available',
                'queue_number' => null,
                'service_name' => null,
                'called_at' => null,
            ];

            if ($currentQueue) {
                $queueData['status'] = $currentQueue->status;
                $queueData['queue_number'] = $currentQueue->number;
                $queueData['service_name'] = $currentQueue->service ? $currentQueue->service->name : 'Layanan';
                $queueData['called_at'] = $currentQueue->called_at ?
                    (is_string($currentQueue->called_at) ? $currentQueue->called_at : $currentQueue->called_at->format('H:i:s')) : null;
            }

            return $queueData;
        })->values();

        return response()->json([
            'zone_name' => $zoneCounter->name,
            'queues' => $queues,
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function zoneCards(KioskCatalogService $catalog, bool $useShortUrl): array
    {
        $colors = [1 => 'blue', 2 => 'green', 3 => 'purple', 4 => 'orange', 5 => 'pink'];

        return collect($catalog->zones())
            ->map(function (array $zone, int $zoneNumber) use ($colors, $useShortUrl): array {
                $url = $useShortUrl
                    ? route("tv.zona{$zoneNumber}")
                    : ($zone['counter_id'] ? route('tv.display.zone', $zone['counter_id']) : null);

                return [
                    'id' => $zone['counter_id'],
                    'number' => $zoneNumber,
                    'name' => $zone['name'],
                    'services' => $zone['service_count'],
                    'instansi' => $zone['institution_count'],
                    'color' => $colors[$zoneNumber] ?? 'gray',
                    'url' => $url,
                    'available' => $zone['counter_id'] !== null,
                ];
            })
            ->values()
            ->all();
    }
}
