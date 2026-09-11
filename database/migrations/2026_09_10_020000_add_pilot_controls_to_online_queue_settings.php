<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_queue_settings', function (Blueprint $table): void {
            $table->boolean('pilot_mode')->default(true)->after('event_enabled');
            $table->json('pilot_service_ids')->nullable()->after('pilot_mode');
        });
    }

    public function down(): void
    {
        Schema::table('online_queue_settings', function (Blueprint $table): void {
            $table->dropColumn(['pilot_mode', 'pilot_service_ids']);
        });
    }
};
