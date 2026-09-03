<?php

namespace App\Exports;

use App\Models\EventQueue;
use App\Models\EventQueueParticipant;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EventParticipantsExport implements FromQuery, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly EventQueue $event,
        private readonly ?string $status = null,
    ) {}

    public function query(): Builder
    {
        return EventQueueParticipant::query()
            ->with('eventQueue')
            ->where('event_queue_id', $this->event->id)
            ->when($this->status && $this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->orderBy('ticket_number');
    }

    public function headings(): array
    {
        return [
            'Nomor Tiket', 'Kode Referensi', 'Nama Peserta', 'NIK', 'Nomor WhatsApp',
            'Tanggal Kedatangan', 'Sesi', 'Status', 'Waktu Daftar', 'Waktu Check-in', 'Waktu Mulai Dilayani',
        ];
    }

    /** @param EventQueueParticipant $participant */
    public function map($participant): array
    {
        $status = match ($participant->status) {
            EventQueueParticipant::STATUS_CHECKED_IN => 'Hadir',
            EventQueueParticipant::STATUS_SERVING => 'Dilayani',
            EventQueueParticipant::STATUS_CANCELED => 'Batal',
            default => 'Terdaftar',
        };

        return [
            $participant->ticket_number,
            $participant->reference_code,
            $participant->name,
            $participant->nik,
            $participant->phone,
            $this->event->arrival_date?->format('d-m-Y') ?? $this->event->starts_at?->format('d-m-Y'),
            $this->event->session_label,
            $status,
            $participant->created_at?->format('d-m-Y H:i:s'),
            $participant->checked_in_at?->format('d-m-Y H:i:s'),
            $participant->served_at?->format('d-m-Y H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
