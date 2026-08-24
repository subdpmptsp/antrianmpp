<?php

namespace App\Listeners;

use App\Models\Attendance;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class RecordAttendanceOnLogin
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        try {
            $user = $event->user ?? Auth::user();

            if (! $user || $user->role === 'admin' || ! $user->is_active) {
                return;
            }

            $instansiId = $user->service?->instansi_id
                ?? $user->counter?->instansi_id;

            Attendance::firstOrCreate(
                ['user_id' => $user->id, 'date' => now()->toDateString()],
                [
                    'instansi_id' => $instansiId,
                    'check_in' => now()->format('H:i:s'),
                    'status' => 'present',
                ],
            );

            Cache::forget('attendance:monthly-recap:'.now()->year);
        } catch (\Exception $e) {
            Log::error('Error in RecordAttendanceOnLogin', [
                'error' => $e->getMessage(),
                'user_id' => $event->user?->id,
            ]);
        }
    }
}
