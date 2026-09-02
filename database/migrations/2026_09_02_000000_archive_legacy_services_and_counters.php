<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'is_archived')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->boolean('is_archived')->default(false)->after('is_accepting_queues')->index();
            });
        }

        if (! Schema::hasColumn('counters', 'is_archived')) {
            Schema::table('counters', function (Blueprint $table): void {
                $table->boolean('is_archived')->default(false)->after('is_active')->index();
            });
        }

        // Data nonaktif yang sudah tidak dipakai adalah arsip, bukan kandidat
        // untuk dinyalakan kembali lewat toggle operasional.
        DB::table('services')->where('is_active', false)->update([
            'is_archived' => true,
            'updated_at' => now(),
        ]);

        // BPOM 4j tetap dicatat sebagai loket operasional yang sementara tutup.
        DB::table('counters')
            ->where('is_active', false)
            ->where('code_loket', '!=', '4j')
            ->update([
                'is_archived' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'is_archived')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->dropIndex(['is_archived']);
                $table->dropColumn('is_archived');
            });
        }

        if (Schema::hasColumn('counters', 'is_archived')) {
            Schema::table('counters', function (Blueprint $table): void {
                $table->dropIndex(['is_archived']);
                $table->dropColumn('is_archived');
            });
        }
    }
};
