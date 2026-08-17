<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('queues')->where('status', 'completed')->update([
            'status' => 'finished',
            'finished_at' => DB::raw('COALESCE(finished_at, updated_at, served_at, called_at, created_at)'),
        ]);

        DB::table('queues')->where('status', 'cancelled')->update([
            'status' => 'canceled',
            'canceled_at' => DB::raw('COALESCE(canceled_at, updated_at, called_at, created_at)'),
        ]);

        Schema::table('queues', function (Blueprint $table): void {
            $table->index(
                ['service_id', 'status', 'created_at'],
                'queues_service_status_created_index',
            );
            $table->index(
                ['counter_id', 'status', 'created_at'],
                'queues_counter_status_created_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('queues', function (Blueprint $table): void {
            $table->dropIndex('queues_service_status_created_index');
            $table->dropIndex('queues_counter_status_created_index');
        });
    }
};
