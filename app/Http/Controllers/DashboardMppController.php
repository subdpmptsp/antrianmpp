<?php

namespace App\Http\Controllers;

use App\Models\Instansi;
use App\Models\Queue;
use App\Models\Service;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardMppController extends Controller
{
    public function index(): View
    {
        return view('dashboard-mpp', [
            'dashboardData' => $this->summary(),
        ]);
    }

    public function data(): JsonResponse
    {
        return response()->json($this->summary());
    }

    /** @return array<string, mixed> */
    private function summary(): array
    {
        return Cache::remember('public-dashboard-mpp-summary', now()->addSeconds(30), function (): array {
            $today = CarbonImmutable::today();
            $startDate = $today->subDays(6);

            $dailyTotals = Queue::query()
                ->whereBetween('created_at', [$startDate->startOfDay(), $today->endOfDay()])
                ->whereNotIn('status', [Queue::STATUS_CANCELED, Queue::STATUS_PRINTING])
                ->selectRaw('DATE(created_at) as queue_date, COUNT(*) as total')
                ->groupBy('queue_date')
                ->pluck('total', 'queue_date');

            $trend = collect(range(0, 6))->map(function (int $offset) use ($startDate, $dailyTotals): array {
                $date = $startDate->addDays($offset);

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->locale('id')->translatedFormat('D'),
                    'total' => (int) ($dailyTotals[$date->toDateString()] ?? 0),
                ];
            })->values()->all();

            $institutions = Instansi::query()
                ->where('is_active', true)
                ->where('is_archived', false)
                ->orderBy('nama_instansi')
                ->limit(5)
                ->pluck('nama_instansi')
                ->values()
                ->all();

            return [
                'tickets_today' => Queue::query()
                    ->whereDate('created_at', $today)
                    ->whereNotIn('status', [Queue::STATUS_CANCELED, Queue::STATUS_PRINTING])
                    ->count(),
                'completed_today' => Queue::query()
                    ->whereDate('created_at', $today)
                    ->where('status', Queue::STATUS_FINISHED)
                    ->count(),
                'institution_count' => Instansi::query()
                    ->where('is_active', true)
                    ->where('is_archived', false)
                    ->count(),
                'service_count' => Service::query()
                    ->where('is_active', true)
                    ->where('is_archived', false)
                    ->count(),
                'zone_count' => Instansi::query()
                    ->where('is_active', true)
                    ->where('is_archived', false)
                    ->whereNotNull('zone')
                    ->where('zone', '<>', '')
                    ->distinct()
                    ->count('zone'),
                'institutions' => $institutions,
                'remaining_institution_count' => max(0, Instansi::query()
                    ->where('is_active', true)
                    ->where('is_archived', false)
                    ->count() - count($institutions)),
                'trend' => $trend,
                'updated_at' => now()->toIso8601String(),
            ];
        });
    }
}
