<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_queue_checkin_challenges', function (Blueprint $table): void {
            $table->id();
            $table->string('token_hash', 64)->unique();
            $table->string('station_code', 50)->default('KIOSK-01');
            $table->foreignId('online_queue_reservation_id')->nullable();
            $table->foreign('online_queue_reservation_id', 'oq_checkin_reservation_fk')
                ->references('id')->on('online_queue_reservations')->nullOnDelete();
            $table->foreignId('queue_id')->nullable();
            $table->foreign('queue_id', 'oq_checkin_queue_fk')
                ->references('id')->on('queues')->nullOnDelete();
            $table->timestamp('expires_at')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_queue_checkin_challenges');
    }
};
