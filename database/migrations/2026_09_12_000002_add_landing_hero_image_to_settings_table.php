<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->string('landing_hero_image')->nullable()->after('landing_city_logo_size');
            $table->string('landing_hero_image_size', 16)->default('medium')->after('landing_hero_image');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn(['landing_hero_image', 'landing_hero_image_size']);
        });
    }
};
