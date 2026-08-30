<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\CounterClosureRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CounterClosureService
{
    public function requestClose(Counter $counter, User $user, string $reason, bool $autoReopen = true): CounterClosureRequest
    {
        if ((int) $user->counter_id !== (int) $counter->id && ! $user->isAdmin()) {
            throw ValidationException::withMessages(['reason' => 'Anda hanya dapat mengajukan penutupan loket yang ditugaskan kepada Anda.']);
        }

        if (! $counter->service || ! $counter->service->is_accepting_queues) {
            throw ValidationException::withMessages(['reason' => 'Loket ini sudah tidak menerima nomor antrean baru.']);
        }

        $reason = trim($reason);

        if ($reason === '' || mb_strlen($reason) > 1000) {
            throw ValidationException::withMessages(['reason' => 'Alasan penutupan loket wajib diisi, maksimal 1.000 karakter.']);
        }

        return DB::transaction(function () use ($counter, $user, $reason, $autoReopen): CounterClosureRequest {
            $hasPendingRequest = CounterClosureRequest::query()
                ->where('counter_id', $counter->id)
                ->where('status', CounterClosureRequest::STATUS_PENDING)
                ->lockForUpdate()
                ->exists();

            if ($hasPendingRequest) {
                throw ValidationException::withMessages(['reason' => 'Permintaan penutupan loket masih menunggu persetujuan admin.']);
            }

            return CounterClosureRequest::create([
                'counter_id' => $counter->id,
                'service_id' => $counter->service_id,
                'requested_by_user_id' => $user->id,
                'reason' => $reason,
                'auto_reopen' => $autoReopen,
                'status' => CounterClosureRequest::STATUS_PENDING,
                'requested_at' => now(),
            ]);
        });
    }

    public function approve(CounterClosureRequest $request, User $admin, ?string $note = null): void
    {
        $this->ensureAdmin($admin);

        DB::transaction(function () use ($request, $admin, $note): void {
            $request = CounterClosureRequest::query()->lockForUpdate()->findOrFail($request->id);

            if ($request->status !== CounterClosureRequest::STATUS_PENDING) {
                throw ValidationException::withMessages(['status' => 'Permintaan ini sudah ditinjau.']);
            }

            $request->update([
                'status' => CounterClosureRequest::STATUS_APPROVED,
                'admin_note' => $note,
                'reviewed_by_user_id' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $this->syncServiceQueueAvailability($request->service_id);
        });

        app(MasterDataCache::class)->invalidate();
    }

    public function reject(CounterClosureRequest $request, User $admin, ?string $note = null): void
    {
        $this->ensureAdmin($admin);

        if ($request->status !== CounterClosureRequest::STATUS_PENDING) {
            throw ValidationException::withMessages(['status' => 'Permintaan ini sudah ditinjau.']);
        }

        $request->update([
            'status' => CounterClosureRequest::STATUS_REJECTED,
            'admin_note' => $note,
            'reviewed_by_user_id' => $admin->id,
            'reviewed_at' => now(),
        ]);
    }

    public function reopen(Counter $counter, User $user): void
    {
        if ((int) $user->counter_id !== (int) $counter->id && ! $user->isAdmin()) {
            throw ValidationException::withMessages(['counter' => 'Anda hanya dapat membuka loket yang ditugaskan kepada Anda.']);
        }

        DB::transaction(function () use ($counter, $user): void {
            $request = CounterClosureRequest::query()
                ->where('counter_id', $counter->id)
                ->where('status', CounterClosureRequest::STATUS_APPROVED)
                ->latest('reviewed_at')
                ->lockForUpdate()
                ->first();

            if (! $request) {
                throw ValidationException::withMessages(['counter' => 'Tidak ada penutupan loket yang dapat dibuka kembali.']);
            }

            $request->update([
                'status' => CounterClosureRequest::STATUS_REOPENED,
                'reopened_by_user_id' => $user->id,
                'reopened_at' => now(),
            ]);

            $this->syncServiceQueueAvailability($counter->service_id);
        });

        app(MasterDataCache::class)->invalidate();
    }

    /**
     * Membuka kembali loket yang sebelumnya disetujui tutup oleh sistem.
     * Hanya dipanggil oleh perintah terjadwal pada hari operasional.
     */
    public function reopenAutomatically(CounterClosureRequest $closureRequest): bool
    {
        $reopened = DB::transaction(function () use ($closureRequest): bool {
            $request = CounterClosureRequest::query()->lockForUpdate()->findOrFail($closureRequest->id);

            if ($request->status !== CounterClosureRequest::STATUS_APPROVED || ! $request->auto_reopen) {
                return false;
            }

            $counter = Counter::withoutGlobalScopes()->findOrFail($request->counter_id);

            $request->update([
                'status' => CounterClosureRequest::STATUS_REOPENED,
                'reopened_by_user_id' => null,
                'reopened_at' => now(),
            ]);

            $this->syncServiceQueueAvailability($counter->service_id);

            return true;
        });

        if ($reopened) {
            app(MasterDataCache::class)->invalidate();
        }

        return $reopened;
    }

    private function ensureAdmin(User $user): void
    {
        if (! $user->isAdmin()) {
            abort(403);
        }
    }

    /**
     * Layanan hanya berhenti menerima nomor baru jika seluruh loket aktifnya
     * telah disetujui tutup. Loket yang sedang menangani antrean tetap dapat
     * memanggil dan menyelesaikan antrean yang sudah masuk.
     */
    private function syncServiceQueueAvailability(int $serviceId): void
    {
        $openCounterExists = Counter::withoutGlobalScopes()
            ->where('service_id', $serviceId)
            ->where('is_active', true)
            ->whereDoesntHave('closureRequests', function ($query): void {
                $query->where('status', CounterClosureRequest::STATUS_APPROVED);
            })
            ->exists();

        DB::table('services')
            ->where('id', $serviceId)
            ->update([
                'is_accepting_queues' => $openCounterExists,
                'updated_at' => now(),
            ]);
    }
}
