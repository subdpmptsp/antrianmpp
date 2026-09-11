<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_queue_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('global_enabled')->default(false);
            $table->boolean('regular_enabled')->default(false);
            $table->boolean('event_enabled')->default(false);
            $table->unsignedSmallInteger('booking_window_days')->default(7);
            $table->timestamps();
        });

        Schema::create('online_queue_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->unsignedInteger('quota');
            $table->unsignedSmallInteger('checkin_open_minutes')->default(15);
            $table->unsignedSmallInteger('checkin_grace_minutes')->default(15);
            $table->string('status', 20)->default('draft');
            $table->timestamps();

            $table->index(['service_id', 'day_of_week', 'status'], 'online_sessions_service_day_status_index');
        });

        Schema::create('online_queue_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('online_queue_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained('services')->restrictOnDelete();
            $table->date('service_date');
            $table->string('booking_code', 20)->unique();
            $table->string('access_token', 64)->unique();
            $table->text('nik');
            $table->string('nik_hash', 64)->index();
            $table->string('nik_last_four', 4);
            $table->string('active_identity_key', 64)->nullable()->unique();
            $table->string('name', 150);
            $table->string('phone', 32);
            $table->string('status', 24)->default('booked')->index();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->index(['online_queue_session_id', 'service_date', 'status'], 'online_reservations_session_date_status_index');
            $table->index(['service_id', 'service_date', 'status'], 'online_reservations_service_date_status_index');
        });

        Schema::table('queues', function (Blueprint $table): void {
            $table->string('source', 20)->default('kiosk')->after('status')->index();
            $table->foreignId('online_queue_reservation_id')->nullable()->after('source')
                ->unique()->constrained('online_queue_reservations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('online_queue_reservation_id');
            $table->dropColumn('source');
        });
        Schema::dropIfExists('online_queue_reservations');
        Schema::dropIfExists('online_queue_sessions');
        Schema::dropIfExists('online_queue_settings');
    }
};
