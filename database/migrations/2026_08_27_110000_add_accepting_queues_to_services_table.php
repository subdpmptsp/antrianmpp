<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'is_accepting_queues')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->boolean('is_accepting_queues')->default(true)->after('is_active');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'is_accepting_queues')) {
            Schema::table('services', function (Blueprint $table): void {
                $table->dropColumn('is_accepting_queues');
            });
        }
    }
};
