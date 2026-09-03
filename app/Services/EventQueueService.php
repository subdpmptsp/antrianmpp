<?php

namespace App\Services;

use App\Models\EventQueue;
use App\Models\EventQueueParticipant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EventQueueService
{
    /** @param array{name:string,nik:string,phone:string} $data */
    public function register(EventQueue $event, array $data): EventQueueParticipant
    {
        return DB::transaction(function () use ($event, $data): EventQueueParticipant {
            /** @var EventQueue $lockedEvent */
            $lockedEvent = EventQueue::query()->lockForUpdate()->findOrFail($event->id);

            if (! $lockedEvent->isAcceptingRegistrations()) {
                throw ValidationException::withMessages(['event' => 'Pendaftaran untuk event ini sudah ditutup.']);
            }

            $existingField = EventQueueParticipant::query()
                ->where('event_queue_id', $lockedEvent->id)
                ->where(function ($query) use ($data): void {
                    $query->where('nik', $data['nik'])->orWhere('phone', $data['phone']);
                })
                ->exists();

            if ($existingField) {
                throw ValidationException::withMessages(['nik' => 'NIK atau nomor WhatsApp ini sudah terdaftar pada event ini.']);
            }

            $registered = EventQueueParticipant::query()
                ->where('event_queue_id', $lockedEvent->id)
                ->where('status', '!=', EventQueueParticipant::STATUS_CANCELED)
                ->count();

            if ($registered >= $lockedEvent->daily_quota) {
                throw ValidationException::withMessages(['event' => 'Kuota pendaftaran event sudah penuh.']);
            }

            $nextNumber = $lockedEvent->last_ticket_number + 1;
            $lockedEvent->forceFill(['last_ticket_number' => $nextNumber])->save();

            $prefix = strtoupper((string) preg_replace('/[^A-Z0-9]/i', '', $lockedEvent->ticket_prefix));
            $prefix = $prefix !== '' ? mb_substr($prefix, 0, 8) : 'E';

            return EventQueueParticipant::create([
                'event_queue_id' => $lockedEvent->id,
                'ticket_number' => $prefix.'-'.str_pad((string) $nextNumber, 3, '0', STR_PAD_LEFT),
                'name' => $data['name'],
                'nik' => $data['nik'],
                'phone' => $data['phone'],
                'qr_token' => Str::random(48),
            ]);
        });
    }

    public function regeneratePublicToken(EventQueue $event): void
    {
        $event->update(['public_token' => Str::random(48)]);
    }

    public function regenerateTvToken(EventQueue $event): void
    {
        $event->update(['tv_token' => Str::random(48)]);
    }
}
