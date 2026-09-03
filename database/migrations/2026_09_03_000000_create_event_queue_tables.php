<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_queues', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->unsignedInteger('daily_quota')->default(60);
            $table->unsignedInteger('session_quota')->nullable();
            $table->unsignedInteger('last_ticket_number')->default(0);
            $table->unsignedSmallInteger('checkin_grace_minutes')->default(30);
            $table->string('status', 20)->default('draft')->index();
            $table->boolean('public_link_enabled')->default(true);
            $table->boolean('mask_participant_names')->default(true);
            $table->string('public_token', 64)->unique();
            $table->string('tv_token', 64)->unique();
            $table->timestamps();
        });

        Schema::create('event_queue_participants', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('event_queue_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number', 30);
            $table->string('name');
            $table->string('nik', 32);
            $table->string('phone', 32);
            $table->string('qr_token', 64)->unique();
            $table->string('status', 20)->default('registered')->index();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();

            $table->unique(['event_queue_id', 'ticket_number']);
            $table->unique(['event_queue_id', 'nik']);
            $table->unique(['event_queue_id', 'phone']);
            $table->index(['event_queue_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_queue_participants');
        Schema::dropIfExists('event_queues');
    }
};
