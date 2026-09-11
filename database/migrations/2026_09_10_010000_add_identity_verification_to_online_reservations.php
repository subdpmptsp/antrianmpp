<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_queue_reservations', function (Blueprint $table): void {
            $table->string('identity_verification_status', 24)->default('not_checked')->after('nik_last_four')->index();
            $table->string('identity_verification_provider', 40)->nullable()->after('identity_verification_status');
            $table->string('identity_verification_reference', 100)->nullable()->after('identity_verification_provider');
            $table->timestamp('identity_verified_at')->nullable()->after('identity_verification_reference');
        });
    }

    public function down(): void
    {
        Schema::table('online_queue_reservations', function (Blueprint $table): void {
            $table->dropIndex(['identity_verification_status']);
            $table->dropColumn([
                'identity_verification_status', 'identity_verification_provider',
                'identity_verification_reference', 'identity_verified_at',
            ]);
        });
    }
};
