<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->string('image_size', 16)->default('medium')->after('image');
            $table->string('landing_city_logo_size', 16)->default('medium')->after('landing_logo_size');
            $table->string('kiosk_logo')->nullable()->after('landing_city_logo_size');
            $table->string('kiosk_logo_size', 16)->default('medium')->after('kiosk_logo');
            $table->string('kiosk_office_logo')->nullable()->after('kiosk_logo_size');
            $table->string('kiosk_office_logo_size', 16)->default('medium')->after('kiosk_office_logo');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table): void {
            $table->dropColumn([
                'image_size',
                'landing_city_logo_size',
                'kiosk_logo',
                'kiosk_logo_size',
                'kiosk_office_logo',
                'kiosk_office_logo_size',
            ]);
        });
    }
};
