<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('antrian_skcks', function (Blueprint $table) {
            $table->date('queue_date')->nullable()->after('antrian');
        });

        DB::table('antrian_skcks')->update([
            'queue_date' => DB::raw('DATE(created_at)'),
        ]);

        Schema::table('antrian_skcks', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->dropUnique(['nomor_whatsapp']);
            $table->date('queue_date')->nullable(false)->change();
            $table->unique(['nik', 'queue_date']);
            $table->unique(['nomor_whatsapp', 'queue_date']);
        });
    }

    public function down(): void
    {
        Schema::table('antrian_skcks', function (Blueprint $table) {
            $table->dropUnique(['nik', 'queue_date']);
            $table->dropUnique(['nomor_whatsapp', 'queue_date']);
            $table->unique('nik');
            $table->unique('nomor_whatsapp');
            $table->dropColumn('queue_date');
        });
    }
};
