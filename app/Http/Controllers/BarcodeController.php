<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class BarcodeController extends Controller
{
    public function show(Request $request)
    {
        $queue = $this->findQueue($request);
        $service = $queue->service;
        $zona = $service->instansi?->zone ?? 'Zona';

        $scanUrl = URL::temporarySignedRoute('barcode.scan', now()->endOfDay(), [
            'queue_id' => $queue->id,
        ]);

        // Log untuk debug

        // Generate QR Code
        $qrCode = QrCode::size(300)
            ->format('svg')
            ->generate($scanUrl);

        $data = [
            'service' => $service,
            'queue' => $queue,
            'zona' => $zona,
            'qrCode' => $qrCode,
            'scanUrl' => $scanUrl,
            'queueNumber' => $queue->number,
            'serviceName' => $service->name,
            'instansiName' => $service->instansi->nama_instansi ?? 'Tidak ada Instansi',
            'tanggal' => now()->translatedFormat('j F Y'),
            'waktu' => now()->format('H:i:s'),
        ];

        return view('barcode.show', $data);
    }

    public function scan(Request $request)
    {
        $queue = $this->findQueue($request);
        $service = $queue->service;
        $zona = $service->instansi?->zone ?? 'Zona';

        $pdfUrl = URL::temporarySignedRoute('struk.generate', now()->addMinutes(15), [
            'queue_id' => $queue->id,
        ]);

        // Untuk mobile, gunakan JavaScript redirect yang lebih reliable
        if ($this->isMobile($request)) {
            return response()->view('barcode.mobile-redirect', [
                'pdfUrl' => $pdfUrl,
                'service' => $service,
                'queue' => $queue,
                'zona' => $zona,
            ]);
        }

        return redirect($pdfUrl);
    }

    private function findQueue(Request $request): Queue
    {
        $queueId = $request->integer('queue_id');
        abort_if($queueId < 1, 404, 'Data tidak ditemukan.');

        return Queue::query()
            ->with('service.instansi')
            ->whereHas('service')
            ->findOrFail($queueId);
    }

    private function isMobile(Request $request): bool
    {
        $userAgent = $request->header('User-Agent', '');
        $mobileKeywords = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod',
            'BlackBerry', 'Windows Phone', 'Opera Mini',
        ];

        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
