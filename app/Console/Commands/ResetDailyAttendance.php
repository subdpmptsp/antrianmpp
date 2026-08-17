<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ResetDailyAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:reset-daily';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset daily attendance - mark incomplete attendances from previous day';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting daily attendance reset...');

        try {
            // Ambil tanggal kemarin
            $yesterday = Carbon::yesterday()->toDateString();

            // Cari semua absensi kemarin yang belum check_out
            $incompleteAttendances = Attendance::where('date', $yesterday)
                ->whereNull('check_out')
                ->get();

            $count = 0;

            foreach ($incompleteAttendances as $attendance) {
                $attendanceDate = $attendance->date->toDateString();

                // Hitung working hours berdasarkan check_in sampai akhir hari (23:59:59)
                $checkIn = Carbon::createFromFormat(
                    'Y-m-d H:i:s',
                    $attendanceDate.' '.$attendance->check_in,
                    config('app.timezone'),
                );
                $endOfDay = Carbon::parse($attendanceDate, config('app.timezone'))->endOfDay();

                // Jika check_in setelah jam 8 pagi, tandai sebagai late
                $lateThreshold = Carbon::parse($attendanceDate.' 08:00:00', config('app.timezone'));
                $status = $checkIn->gt($lateThreshold) ? 'late' : 'present';

                // Hitung working hours dalam menit
                $workingHours = (int) $checkIn->diffInMinutes($endOfDay);

                // Update attendance
                $attendance->update([
                    'check_out' => $endOfDay->toTimeString(),
                    'status' => $status,
                    'working_hours' => $workingHours,
                ]);

                $count++;
            }

            $this->info("Successfully reset {$count} incomplete attendances from {$yesterday}");

            Log::info('Daily attendance reset completed', [
                'date' => $yesterday,
                'count' => $count,
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Error resetting daily attendance: '.$e->getMessage());

            Log::error('Error in daily attendance reset', [
                'error' => $e->getMessage(),
            ]);

            return Command::FAILURE;
        }
    }
}
