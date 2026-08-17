<?php

namespace App\Listeners;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RecordAttendanceOnLogout
{
    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        try {
            $user = $event->user ?? null;

            if (! $user || $user->role === 'admin') {
                return;
            }

            $attendance = Attendance::query()
                ->where('user_id', $user->id)
                ->whereDate('date', now()->toDateString())
                ->whereNull('check_out')
                ->first();

            if (! $attendance) {
                return;
            }

            $checkOut = now();
            $attendanceDate = $attendance->date->toDateString();
            $checkIn = Carbon::createFromFormat(
                'Y-m-d H:i:s',
                $attendanceDate.' '.$attendance->check_in,
                $checkOut->timezone,
            );

            $attendance->update([
                'check_out' => $checkOut->format('H:i:s'),
                'working_hours' => (int) $checkIn->diffInMinutes($checkOut),
            ]);
        } catch (\Throwable $e) {
            Log::error('Error in RecordAttendanceOnLogout', [
                'error' => $e->getMessage(),
                'user_id' => $event->user?->id,
            ]);
        }
    }
}
