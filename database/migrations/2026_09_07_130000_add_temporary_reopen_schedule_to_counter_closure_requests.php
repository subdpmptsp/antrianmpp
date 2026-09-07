<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('counter_closure_requests', function (Blueprint $table): void {
            $table->timestamp('scheduled_reopen_at')->nullable()->after('auto_reopen');
            $table->boolean('closes_service_queues')->default(false)->after('scheduled_reopen_at');
            $table->index(['status', 'scheduled_reopen_at'], 'closure_requests_status_scheduled_reopen_index');
        });
    }

    public function down(): void
    {
        Schema::table('counter_closure_requests', function (Blueprint $table): void {
            $table->dropIndex('closure_requests_status_scheduled_reopen_index');
            $table->dropColumn(['scheduled_reopen_at', 'closes_service_queues']);
        });
    }
};
