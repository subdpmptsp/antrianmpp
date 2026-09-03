<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            $table->json('queue_schedule')->nullable()->after('is_accepting_queues');
            $table->unsignedInteger('daily_queue_quota')->nullable()->after('queue_schedule');
            $table->string('queue_override', 20)->nullable()->after('daily_queue_quota');
            $table->text('queue_override_reason')->nullable()->after('queue_override');
            $table->timestamp('queue_override_until')->nullable()->after('queue_override_reason');
        });

        Schema::create('service_queue_date_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->date('date');
            $table->boolean('is_closed')->default(true);
            $table->string('reason', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['service_id', 'date']);
        });

        Schema::create('service_queue_override_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 40);
            $table->text('reason')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->index(['service_id', 'created_at']);
        });

        $services = DB::table('services')
            ->leftJoin('instansis', 'instansis.instansi_id', '=', 'services.instansi_id')
            ->select('services.id', 'instansis.work_days_per_week')
            ->get();

        foreach ($services as $service) {
            $workDays = in_array((int) $service->work_days_per_week, [5, 6], true)
                ? (int) $service->work_days_per_week
                : 5;
            $schedule = [];

            foreach (range(1, $workDays) as $day) {
                $schedule[] = [
                    'day' => $day,
                    'is_open' => true,
                    'opens_at' => '08:00',
                    'last_ticket_at' => '14:30',
                    'closes_at' => '15:00',
                ];
            }

            DB::table('services')->where('id', $service->id)->update([
                'queue_schedule' => json_encode($schedule),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('service_queue_override_logs');
        Schema::dropIfExists('service_queue_date_overrides');

        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn([
                'queue_schedule', 'daily_queue_quota', 'queue_override',
                'queue_override_reason', 'queue_override_until',
            ]);
        });
    }
};
