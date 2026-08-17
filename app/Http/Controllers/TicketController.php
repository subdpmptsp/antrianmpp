<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class TicketController extends Controller
{
    public function queuePdf(Queue $queue)
    {
        $queue->load('service.instansi.counter');
        $service = $queue->service;
        abort_unless($service, 404, 'Layanan tidak ditemukan.');

        // Build QR as data URI so DomPDF can embed it easily
        $payload = [
            'mall' => 'MALL PELAYANAN PUBLIK KOTA SURABAYA',
            'zona' => $service->instansi?->counter?->name ?? 'Zona',
            'loket' => $service?->instansi?->nama_instansi ?? '-',
            'layanan' => $service?->name ?? '-',
            'nomor' => $queue->number,
            'tanggal' => $queue->created_at->translatedFormat('j F Y'),
            'waktu' => $queue->created_at->format('H:i:s'),
            // Payload untuk QR/Barcode
            'qrData' => json_encode([
                'queue_id' => $queue->id,
                'number' => $queue->number,
                'service' => $service?->name,
                'created_at' => $queue->created_at?->toIso8601String(),
            ]),
        ];

        $qr = QrCode::create($payload['qrData'])
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh)
            ->setSize(280)
            ->setMargin(0);
        $writer = new PngWriter;
        $qrDataUri = $writer->write($qr)->getDataUri();

        $data = $payload + ['qrDataUri' => $qrDataUri];

        $pdf = Pdf::loadView('pdf.ticket', $data)->setPaper('a6', 'portrait');

        return $pdf->stream('ticket-'.$queue->number.'.pdf');
    }
}
