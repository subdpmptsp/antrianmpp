<?php

namespace App\Http\Controllers;

use App\Models\Instansi;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MonitoringReportPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = Carbon::parse($validated['from'])->startOfDay();
        $to = Carbon::parse($validated['to'])->endOfDay();
        abort_if($from->diffInDays($to) > 31, 422, 'Rentang pratinjau PDF maksimal 31 hari.');

        $institutions = $this->reportRows($from, $to);
        $pdf = Pdf::loadView('pdf.monitoring-rekap-layanan', [
            'from' => $from,
            'to' => $to,
            'institutions' => $institutions,
            'generatedAt' => now('Asia/Jakarta'),
        ])->setPaper('a4', 'portrait');

        $filename = 'rekap-layanan-'.$from->format('Y-m-d').'-sd-'.$to->format('Y-m-d').'.pdf';

        return $pdf->stream($filename, ['Attachment' => false]);
    }

    /** @return Collection<int, Instansi> */
    private function reportRows(Carbon $from, Carbon $to): Collection
    {
        $institutions = Instansi::query()
            ->where('is_active', true)
            ->where('is_archived', false)
            ->whereHas('services', fn ($query) => $query->where('is_active', true)->where('is_archived', false))
            ->with(['services' => fn ($query) => $query->where('is_active', true)->where('is_archived', false)->orderBy('prefix')])
            ->orderBy('nama_instansi')
            ->get();

        $serviceIds = $institutions->flatMap->services->pluck('id');
        $counts = DB::table('queues')
            ->whereIn('service_id', $serviceIds)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('service_id, COUNT(*) as total')
            ->groupBy('service_id')
            ->pluck('total', 'service_id');

        return $institutions->map(function (Instansi $institution) use ($counts): Instansi {
            $institution->services->each(function ($service) use ($counts): void {
                $service->period_total = (int) ($counts[$service->id] ?? 0);
            });
            $institution->period_total = (int) $institution->services->sum('period_total');

            return $institution;
        });
    }
}
