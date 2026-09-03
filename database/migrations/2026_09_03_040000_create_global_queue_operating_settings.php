<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_operating_settings', function (Blueprint $table): void {
            $table->id();
            $table->json('weekly_schedule');
            $table->unsignedSmallInteger('cutoff_minutes')->default(30);
            $table->unsignedInteger('default_daily_quota')->nullable();
            $table->timestamps();
        });

        Schema::create('counter_queue_schedule_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('counter_id')->constrained('counters')->cascadeOnDelete();
            $table->string('mode', 20)->default('default');
            $table->json('weekly_schedule')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('counter_id');
        });

        Schema::create('counter_queue_override_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('counter_id')->constrained('counters')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);
            $table->text('reason')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->json('snapshot')->nullable();
            $table->timestamps();
            $table->index(['counter_id', 'created_at']);
        });

        DB::table('queue_operating_settings')->insert([
            'weekly_schedule' => json_encode([
                ['day' => 1, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00'],
                ['day' => 2, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00'],
                ['day' => 3, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00'],
                ['day' => 4, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:00'],
                ['day' => 5, 'is_open' => true, 'opens_at' => '08:00', 'closes_at' => '16:30'],
                ['day' => 6, 'is_open' => false, 'opens_at' => null, 'closes_at' => null],
                ['day' => 7, 'is_open' => false, 'opens_at' => null, 'closes_at' => null],
            ]),
            'cutoff_minutes' => 30,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('counter_queue_override_logs');
        Schema::dropIfExists('counter_queue_schedule_overrides');
        Schema::dropIfExists('queue_operating_settings');
    }
};
