<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('queues')
            ->whereIn('status', ['called', 'serving', 'finished'])
            ->whereNull('called_at')
            ->update(['called_at' => DB::raw('COALESCE(updated_at, created_at)')]);

        DB::table('queues')
            ->whereIn('status', ['serving', 'finished'])
            ->whereNull('served_at')
            ->update(['served_at' => DB::raw('COALESCE(called_at, updated_at, created_at)')]);

        DB::table('queues')
            ->where('status', 'finished')
            ->whereNull('finished_at')
            ->update(['finished_at' => DB::raw('COALESCE(updated_at, served_at, called_at, created_at)')]);

        DB::table('queues')
            ->where('status', 'canceled')
            ->whereNull('canceled_at')
            ->update(['canceled_at' => DB::raw('COALESCE(updated_at, called_at, created_at)')]);
    }

    public function down(): void
    {
        // Historical timestamps cannot be distinguished safely after backfilling.
    }
};
