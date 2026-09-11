<?php

namespace App\Services;

use App\Models\OnlineQueueSession;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OnlineQueueScheduleService
{
    /** @param array<string, mixed> $data */
    public function updateSession(OnlineQueueSession $session, array $data): OnlineQueueSession
    {
        return DB::transaction(function () use ($session, $data): OnlineQueueSession {
            $locked = OnlineQueueSession::query()->lockForUpdate()->findOrFail($session->id);
            $startsAt = substr((string) $data['starts_at'], 0, 5);
            $endsAt = substr((string) $data['ends_at'], 0, 5);
            $quota = (int) $data['quota'];
            $activeReservations = $locked->reservations()
                ->whereIn('status', ['booked', 'checked_in'])
                ->count();

            if ($startsAt === '' || $endsAt === '' || $startsAt >= $endsAt) {
                throw ValidationException::withMessages(['ends_at' => 'Jam selesai harus lebih akhir dari jam mulai.']);
            }
            if ($quota < max(1, $activeReservations)) {
                throw ValidationException::withMessages(['quota' => 'Kuota tidak boleh lebih kecil dari '.$activeReservations.' reservasi aktif.']);
            }

            $scheduleChanged = (int) $locked->service_id !== (int) $data['service_id']
                || (int) $locked->day_of_week !== (int) $data['day_of_week']
                || substr((string) $locked->starts_at, 0, 5) !== $startsAt
                || substr((string) $locked->ends_at, 0, 5) !== $endsAt;

            if ($activeReservations > 0 && $scheduleChanged) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Layanan, hari, dan jam tidak dapat diubah karena sesi sudah memiliki reservasi aktif.',
                ]);
            }

            $duplicate = OnlineQueueSession::query()
                ->whereKeyNot($locked->id)
                ->where('service_id', (int) $data['service_id'])
                ->where('day_of_week', (int) $data['day_of_week'])
                ->where('starts_at', $startsAt)
                ->where('ends_at', $endsAt)
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['starts_at' => 'Sesi dengan layanan, hari, dan jam yang sama sudah tersedia.']);
            }

            $locked->update([
                'service_id' => (int) $data['service_id'],
                'day_of_week' => (int) $data['day_of_week'],
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'quota' => $quota,
                'checkin_open_minutes' => (int) $data['checkin_open_minutes'],
                'checkin_grace_minutes' => (int) $data['checkin_grace_minutes'],
                'status' => (string) $data['status'],
            ]);

            return $locked->refresh();
        });
    }

    /**
     * @param array{
     *   service_ids: array<int, int|string>, days: array<int, int|string>, starts_at: string,
     *   ends_at: string, quota: int|string, checkin_open_minutes: int|string,
     *   checkin_grace_minutes: int|string, status: string
     * } $data
     * @return Collection<int, OnlineQueueSession>
     */
    public function createBulk(array $data): Collection
    {
        $serviceIds = collect($data['service_ids'] ?? [])->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        $days = collect($data['days'] ?? [])->map(fn ($day): int => (int) $day)
            ->filter(fn (int $day): bool => $day >= 1 && $day <= 7)->unique()->values();
        $startsAt = substr((string) $data['starts_at'], 0, 5);
        $endsAt = substr((string) $data['ends_at'], 0, 5);
        $quota = (int) $data['quota'];
        $status = (string) $data['status'];

        if ($serviceIds->isEmpty() || $days->isEmpty()) {
            throw ValidationException::withMessages(['service_ids' => 'Pilih minimal satu layanan dan satu hari.']);
        }
        if ($startsAt === '' || $endsAt === '' || $startsAt >= $endsAt) {
            throw ValidationException::withMessages(['ends_at' => 'Jam selesai harus lebih akhir dari jam mulai.']);
        }
        if ($quota < 1) {
            throw ValidationException::withMessages(['quota' => 'Kuota minimal satu pemohon.']);
        }
        if (! in_array($status, [OnlineQueueSession::STATUS_DRAFT, OnlineQueueSession::STATUS_ACTIVE, OnlineQueueSession::STATUS_CLOSED], true)) {
            throw ValidationException::withMessages(['status' => 'Status sesi tidak valid.']);
        }

        return DB::transaction(function () use ($serviceIds, $days, $startsAt, $endsAt, $quota, $status, $data): Collection {
            $services = Service::query()
                ->whereIn('id', $serviceIds)
                ->where('is_active', true)
                ->where('is_archived', false)
                ->lockForUpdate()
                ->get();

            if ($services->count() !== $serviceIds->count()) {
                throw ValidationException::withMessages(['service_ids' => 'Salah satu layanan tidak aktif atau sudah diarsipkan.']);
            }

            $created = collect();
            foreach ($services as $service) {
                foreach ($days as $day) {
                    $created->push(OnlineQueueSession::query()->updateOrCreate(
                        [
                            'service_id' => $service->id,
                            'day_of_week' => $day,
                            'starts_at' => $startsAt,
                            'ends_at' => $endsAt,
                        ],
                        [
                            'quota' => $quota,
                            'checkin_open_minutes' => (int) $data['checkin_open_minutes'],
                            'checkin_grace_minutes' => (int) $data['checkin_grace_minutes'],
                            'status' => $status,
                        ],
                    ));
                }
            }

            return $created;
        });
    }
}
