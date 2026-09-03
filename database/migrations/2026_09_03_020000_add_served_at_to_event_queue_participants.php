<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_queue_participants', function (Blueprint $table): void {
            $table->timestamp('served_at')->nullable()->after('checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_queue_participants', function (Blueprint $table): void {
            $table->dropColumn('served_at');
        });
    }
};
