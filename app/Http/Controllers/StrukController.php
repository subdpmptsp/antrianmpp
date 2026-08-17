<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Service;
use App\Services\QueueService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class StrukController extends Controller
{
    public function __construct(private readonly QueueService $queueService) {}

    public function generateStruk(Request $request)
    {
        $queueId = $request->integer('queue_id');
        abort_if($queueId < 1, 404, 'Antrian tidak ditemukan.');

        $queue = Queue::query()
            ->with('service.instansi.counter')
            ->findOrFail($queueId);
        $service = $queue->service;
        abort_unless($service, 404, 'Layanan tidak ditemukan.');

        // Siapkan data struk
        $strukData = [
            'mall' => 'MALL PELAYANAN PUBLIK',
            'kota' => 'KOTA SURABAYA',
            'zona' => $service->instansi?->counter?->name ?? 'Zona',
            'loket' => $service->instansi?->nama_instansi ?? 'Loket',
            'layanan' => $service->name,
            'nomor' => $queue->number,
            'tanggal' => $queue->created_at->translatedFormat('j F Y'),
            'waktu' => $queue->created_at->format('H:i:s'),
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.struk-antrian', ['data' => $strukData])
            ->setPaper([0, 0, 226.77, 226.77], 'portrait') // 80mm x 80mm dalam points (persegi)
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'Courier New',
            ]);

        return $pdf->stream('struk-antrian-'.$queue->number.'.pdf');
    }

    public function printTicket(Queue $queue)
    {
        $queue->loadMissing('service.instansi.counter');
        abort_unless($queue->service, 404, 'Layanan tidak ditemukan.');

        return response()
            ->view('print.ticket', ['queue' => $queue])
            ->header('Cache-Control', 'no-store, private');
    }

    public function previewStruk(Request $request)
    {
        $serviceId = $request->input('service_id');

        // Ambil data service
        $service = Service::with('instansi')->find($serviceId);
        if (! $service) {
            return response()->json(['error' => 'Layanan tidak ditemukan'], 404);
        }

        // Generate nomor antrian (preview, tidak disimpan)
        $queueNumber = $this->queueService->generateNumber($service->id);

        // Siapkan data struk
        $strukData = [
            'mall' => 'MALL PELAYANAN PUBLIK',
            'kota' => 'KOTA SURABAYA',
            'zona' => $service->instansi?->counter?->name ?? 'Zona',
            'loket' => $service->instansi?->nama_instansi ?? 'Loket',
            'layanan' => $service->name,
            'nomor' => $queueNumber,
            'tanggal' => now()->translatedFormat('j F Y'),
            'waktu' => now()->format('H:i:s'),
        ];

        // Generate PDF untuk preview
        $pdf = Pdf::loadView('pdf.struk-antrian', ['data' => $strukData])
            ->setPaper([0, 0, 226.77, 226.77], 'portrait') // 80mm x 80mm dalam points (persegi)
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'Courier New',
            ]);

        return $pdf->stream('preview-struk-antrian.pdf');
    }
}
