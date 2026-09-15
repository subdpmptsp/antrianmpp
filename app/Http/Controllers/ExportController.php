<?php

namespace App\Http\Controllers;

use App\Exports\OnlineQueueReservationsExport;
use App\Exports\QueueChannelRecapExport;
use App\Exports\RekapLayananExport;
use App\Models\AntrianSkck;
use App\Models\Service;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function onlineQueueReservations(Request $request)
    {
        $data = $request->validate([
            'date' => ['nullable', 'date'],
            'status' => ['nullable', 'in:all,booked,checked_in,canceled,expired'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $status = $data['status'] ?? 'all';
        $date = isset($data['date']) ? date('Y-m-d', strtotime($data['date'])) : null;
        $fileDate = $date ?: now('Asia/Jakarta')->format('Y-m-d');

        return Excel::download(new OnlineQueueReservationsExport(
            $date,
            $status,
            isset($data['service_id']) ? (int) $data['service_id'] : null,
            $data['search'] ?? null,
        ), "pendaftar-antrean-online-{$fileDate}.xlsx");
    }

    public function queueChannelRecap(Request $request)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
        ]);
        $from = $data['from'] ?? now('Asia/Jakarta')->startOfMonth()->toDateString();
        $to = $data['to'] ?? now('Asia/Jakarta')->toDateString();

        return Excel::download(
            new QueueChannelRecapExport($from, $to, $request->integer('service_id') ?: null),
            'rekap-kanal-antrean-'.$from.'-sd-'.$to.'.xlsx',
        );
    }

    public function queueChannelRecapPdf(Request $request)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
        ]);
        $from = $data['from'] ?? now('Asia/Jakarta')->startOfMonth()->toDateString();
        $to = $data['to'] ?? now('Asia/Jakarta')->toDateString();
        $serviceId = $request->integer('service_id') ?: null;
        $serviceName = $serviceId ? Service::query()->find($serviceId)?->name : null;
        $rows = (new QueueChannelRecapExport($from, $to, $serviceId))->collection();

        return Pdf::loadView('pdf.queue-channel-recap', [
            'from' => Carbon::parse($from, 'Asia/Jakarta'),
            'to' => Carbon::parse($to, 'Asia/Jakarta'),
            'serviceName' => $serviceName,
            'rows' => $rows,
            'generatedAt' => now('Asia/Jakarta'),
        ])
            ->setPaper('a4', 'landscape')
            ->stream('rekap-kanal-antrean-'.$from.'-sd-'.$to.'.pdf', ['Attachment' => false]);
    }

    private $bulan = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    public function rekapLayanan(Request $request)
    {
        $from = $request->query('from', now()->toDateString());
        $to = $request->query('to', now()->toDateString());
        $zoneId = $request->string('zone_id')->toString() ?: null;

        $fileName = "rekap_layanan_{$from}_sd_{$to}".($zoneId && $zoneId !== 'all' ? "_zona_{$zoneId}" : '').'.xlsx';

        return Excel::download(new RekapLayananExport($from, $to, $zoneId), $fileName);
    }

    public function cetakSkck(Request $request, $id)
    {
        $id = str_replace('SKCK', '', $id);

        $antrianSkck = AntrianSkck::find($id);

        if ($antrianSkck == null) {
            abort(404);
        }

        $logo = public_path('logo_pemkot.png');

        $logoBase64 = base64_encode(file_get_contents($logo));
        $tanggal = date('j', strtotime($antrianSkck->created_at));
        $bulanAngka = date('n', strtotime($antrianSkck->created_at));
        $tahun = date('Y', strtotime($antrianSkck->created_at));

        $format = $tanggal.' '.$this->bulan[$bulanAngka].' '.$tahun;
        $pdf = Pdf::loadView('antrian-skck', [
            'logo' => $logoBase64,
            'nomor' => str_pad($antrianSkck->antrian, 3, '0', STR_PAD_LEFT),
            'tanggal' => $format,
            'nama' => $antrianSkck->nama,
        ]);

        $customPaper = [0, 0, 360, 360];

        return $pdf->setPaper($customPaper)->stream($id.'.pdf');
    }
}
