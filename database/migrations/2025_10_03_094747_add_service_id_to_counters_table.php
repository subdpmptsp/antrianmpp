<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('counters', 'service_id')) {
            Schema::table('counters', function (Blueprint $table) {
                $table->foreignId('service_id')->nullable()->after('instansi_id');
            });
        }

        $hasForeignKey = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'counters')
            ->where('COLUMN_NAME', 'service_id')
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();

        if (!$hasForeignKey) {
            Schema::table('counters', function (Blueprint $table) {
                $table->foreign('service_id')->references('id')->on('services')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('counters', 'service_id')) {
            Schema::table('counters', function (Blueprint $table) {
                $table->dropConstrainedForeignId('service_id');
            });
        }
    }
};
