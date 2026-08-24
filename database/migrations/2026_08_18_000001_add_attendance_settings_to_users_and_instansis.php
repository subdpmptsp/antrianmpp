<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role')->index();
        });

        Schema::table('instansis', function (Blueprint $table) {
            $table->unsignedTinyInteger('work_days_per_week')
                ->default(5)
                ->after('nama_instansi')
                ->comment('5 = Senin-Jumat, 6 = Senin-Sabtu');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('instansis', function (Blueprint $table) {
            $table->dropColumn('work_days_per_week');
        });
    }
};
