<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_queue_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('online_queue_reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40)->index();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['online_queue_reservation_id', 'created_at'], 'oq_audits_reservation_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_queue_audits');
    }
};
