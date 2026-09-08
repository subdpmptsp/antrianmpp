<?php

namespace App\Services;

use App\Models\Counter;
use App\Models\CounterClosureRequest;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CounterClosureService
{
    public function requestClose(
        Counter $counter,
        User $user,
        string $reason,
        bool $autoReopen = true,
        ?Carbon $scheduledReopenAt = null,
    ): CounterClosureRequest
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

        if ($scheduledReopenAt && $scheduledReopenAt->lessThanOrEqualTo(now('Asia/Jakarta'))) {
            throw ValidationException::withMessages([
                'scheduled_reopen_at' => 'Waktu aktif kembali harus setelah waktu saat ini.',
            ]);
        }

        return DB::transaction(function () use ($counter, $user, $reason, $autoReopen, $scheduledReopenAt): CounterClosureRequest {
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
                // Seluruh pengajuan tutup loket bersifat sementara. Tidak ada
                // penutupan dari pengajuan petugas yang boleh terbawa ke hari
                // operasional berikutnya hanya karena lupa dibuka kembali.
                'auto_reopen' => true,
                'scheduled_reopen_at' => $scheduledReopenAt,
                // Fitur jeda seluruh layanan belum diaktifkan pada operasional.
                'closes_service_queues' => false,
                'status' => CounterClosureRequest::STATUS_PENDING,
                'requested_at' => now(),
            ]);
        });
    }

    public function approve(
        CounterClosureRequest $request,
        User $admin,
        ?string $note = null,
    ): void
    {
        $this->ensureAdmin($admin);

        if ($request->closes_service_queues && ! $request->scheduled_reopen_at) {
            throw ValidationException::withMessages([
                'scheduled_reopen_at' => 'Waktu layanan dibuka kembali wajib diisi untuk istirahat sementara.',
            ]);
        }

        if ($request->scheduled_reopen_at && $request->scheduled_reopen_at->lessThanOrEqualTo(now('Asia/Jakarta'))) {
            throw ValidationException::withMessages([
                'scheduled_reopen_at' => 'Waktu aktif kembali harus setelah waktu saat ini.',
            ]);
        }

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

            if ($request->closes_service_queues) {
                $this->pauseServiceQueues($request->service_id, $request->scheduled_reopen_at, $request->reason);
            }

            $this->syncCounterQueueAvailability(
                Counter::withoutGlobalScopes()->findOrFail($request->counter_id),
            );
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

    /**
     * Menutup administrasi pengajuan lama yang belum ditinjau. Pengajuan
     * pending tidak pernah mengubah status loket, sehingga proses ini hanya
     * membebaskan petugas untuk dapat mengajukan kembali pada hari kerja baru.
     */
    public function expirePending(CounterClosureRequest $closureRequest): bool
    {
        return DB::transaction(function () use ($closureRequest): bool {
            $request = CounterClosureRequest::query()->lockForUpdate()->findOrFail($closureRequest->id);

            if ($request->status !== CounterClosureRequest::STATUS_PENDING) {
                return false;
            }

            $request->update([
                'status' => CounterClosureRequest::STATUS_EXPIRED,
                'admin_note' => 'Kedaluwarsa otomatis pada awal hari operasional berikutnya karena belum ditinjau admin.',
            ]);

            return true;
        });
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

            $this->syncCounterQueueAvailability(
                Counter::withoutGlobalScopes()->findOrFail($counter->id),
            );
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
            $today = now('Asia/Jakarta')->startOfDay();

            $scheduledReopenDue = $request->scheduled_reopen_at
                && $request->scheduled_reopen_at->lessThanOrEqualTo(now('Asia/Jakarta'));
            $approvedOnPreviousDay = $request->reviewed_at
                && $request->reviewed_at->copy()->setTimezone('Asia/Jakarta')->lessThan($today);

            if ($request->status !== CounterClosureRequest::STATUS_APPROVED || (! $scheduledReopenDue && ! $approvedOnPreviousDay)) {
                return false;
            }

            $counter = Counter::withoutGlobalScopes()->findOrFail($request->counter_id);

            $request->update([
                'status' => CounterClosureRequest::STATUS_REOPENED,
                'reopened_by_user_id' => null,
                'reopened_at' => now(),
            ]);

            $this->syncCounterQueueAvailability($counter);

            return true;
        });

        if ($reopened) {
            app(MasterDataCache::class)->invalidate();
        }

        return $reopened;
    }

    /**
     * Pengaman ketika scheduler tengah malam tidak berjalan, misalnya karena
     * server dimatikan. Dipanggil saat kiosk memeriksa layanan agar penutupan
     * sementara hari sebelumnya langsung dipulihkan pada akses pertama.
     */
    public function recoverOverdueClosuresForService(int $serviceId): int
    {
        $now = now('Asia/Jakarta');
        $today = $now->copy()->startOfDay();
        $calendar = app(WorkingCalendarService::class);
        $requests = CounterClosureRequest::query()
            ->with('counter.instansi')
            ->where('service_id', $serviceId)
            ->where('status', CounterClosureRequest::STATUS_APPROVED)
            ->where(function ($query) use ($today, $now): void {
                $query->where('reviewed_at', '<', $today)
                    ->orWhere(function ($scheduled) use ($now): void {
                        $scheduled->whereNotNull('scheduled_reopen_at')
                            ->where('scheduled_reopen_at', '<=', $now);
                    });
            })
            ->orderBy('reviewed_at')
            ->get();

        $recovered = 0;

        foreach ($requests as $request) {
            $counter = $request->counter;
            $isTimedReopen = $request->scheduled_reopen_at !== null;

            if (! $counter?->instansi || (! $isTimedReopen && ! $calendar->isWorkingDay($counter->instansi, $today))) {
                continue;
            }

            if ($this->reopenAutomatically($request)) {
                $recovered++;
            }
        }

        return $recovered;
    }

    /** Hapus penutupan layanan sementara yang waktunya sudah berakhir. */
    public function clearExpiredServiceQueuePauses(): int
    {
        $now = now('Asia/Jakarta');
        $services = Service::query()
            ->where('queue_override', 'force_closed')
            ->whereNotNull('queue_override_until')
            ->where('queue_override_until', '<=', $now)
            ->get();

        foreach ($services as $service) {
            $service->update([
                'queue_override' => null,
                'queue_override_reason' => null,
                'queue_override_until' => null,
            ]);
        }

        if ($services->isNotEmpty()) {
            app(MasterDataCache::class)->invalidate();
        }

        return $services->count();
    }

    private function ensureAdmin(User $user): void
    {
        if (! $user->isAdmin()) {
            abort(403);
        }
    }

    /**
     * Penutupan ini berada di level layanan: kartu tetap terlihat di kiosk,
     * tetapi penerbitan nomor ditolak sampai waktu yang ditentukan admin.
     */
    private function pauseServiceQueues(int $serviceId, Carbon $until, string $reason): void
    {
        $service = Service::query()->lockForUpdate()->findOrFail($serviceId);
        $currentUntil = $service->queue_override === 'force_closed'
            ? $service->queue_override_until
            : null;
        $effectiveUntil = $currentUntil && $currentUntil->greaterThan($until)
            ? $currentUntil
            : $until;

        $service->update([
            'queue_override' => 'force_closed',
            'queue_override_reason' => 'Istirahat sementara: '.$reason,
            'queue_override_until' => $effectiveUntil,
        ]);
    }

    /**
     * Layanan hanya berhenti menerima nomor baru jika seluruh loket aktifnya
     * telah disetujui tutup. Loket yang sedang menangani antrean tetap dapat
     * memanggil dan menyelesaikan antrean yang sudah masuk.
     */
    /**
     * Satu loket dapat menangani layanan utama maupun layanan bantuan.
     * Saat loket ditutup/dibuka, seluruh layanan yang dapat ditanganinya
     * harus disinkronkan agar status kiosk tidak tertinggal.
     */
    private function syncCounterQueueAvailability(Counter $counter): void
    {
        $counter->callableServiceIds()
            ->each(fn (int $serviceId) => $this->synchronizeServiceQueueAvailability($serviceId));
    }

    /**
     * Sinkronkan status penerbitan tiket berdasarkan penutupan loket. Sebagian
     * besar layanan tetap menerima nomor selama ada loket lain yang aktif.
     * ETLE adalah pengecualian operasional: satu persetujuan tutup menghentikan
     * seluruh pengambilan nomor ETLE sampai loket tersebut dibuka kembali.
     */
    public function synchronizeServiceQueueAvailability(int $serviceId): void
    {
        $service = Service::query()->with('instansi')->findOrFail($serviceId);
        $closeEntireService = collect(config('kiosk.close_entire_service_when_any_counter_closes', []))
            ->contains(function (array $rule) use ($service): bool {
                return ($rule['service_prefix'] ?? null) === $service->prefix
                    && ($rule['instansi'] ?? null) === $service->instansi?->nama_instansi;
            });

        $counterQuery = Counter::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('is_archived', false)
            ->where(function ($query) use ($serviceId): void {
                $query->where('service_id', $serviceId)
                    ->orWhereHas('additionalServices', fn ($additional) => $additional->whereKey($serviceId));
            });

        $isAcceptingQueues = $closeEntireService
            ? ! (clone $counterQuery)->whereHas('closureRequests', function ($query): void {
                $query->where('status', CounterClosureRequest::STATUS_APPROVED);
            })->exists()
            : (clone $counterQuery)->whereDoesntHave('closureRequests', function ($query): void {
                $query->where('status', CounterClosureRequest::STATUS_APPROVED);
            })->exists();

        DB::table('services')
            ->where('id', $serviceId)
            ->update([
                'is_accepting_queues' => $isAcceptingQueues,
                'updated_at' => now(),
            ]);
    }
}
