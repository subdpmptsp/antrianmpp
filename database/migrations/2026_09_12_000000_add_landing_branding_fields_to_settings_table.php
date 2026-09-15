<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->string('landing_logo')->nullable()->after('image');
            $table->string('landing_city_logo')->nullable()->after('landing_logo');
            $table->string('landing_logo_size', 16)->default('medium')->after('landing_city_logo');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn(['landing_logo', 'landing_city_logo', 'landing_logo_size']);
        });
    }
};
