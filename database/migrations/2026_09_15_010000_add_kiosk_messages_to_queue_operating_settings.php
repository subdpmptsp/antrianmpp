<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queue_operating_settings', function (Blueprint $table): void {
            $table->json('kiosk_messages')->nullable()->after('default_daily_quota');
        });
    }

    public function down(): void
    {
        Schema::table('queue_operating_settings', function (Blueprint $table): void {
            $table->dropColumn('kiosk_messages');
        });
    }
};
