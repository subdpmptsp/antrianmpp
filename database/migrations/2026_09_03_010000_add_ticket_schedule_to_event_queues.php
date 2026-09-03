<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_queues', function (Blueprint $table): void {
            $table->string('ticket_prefix', 8)->default('E')->after('session_quota');
            $table->string('reference_prefix', 40)->nullable()->after('ticket_prefix');
            $table->date('arrival_date')->nullable()->after('ends_at');
            $table->string('session_label', 80)->nullable()->after('arrival_date');
        });
    }

    public function down(): void
    {
        Schema::table('event_queues', function (Blueprint $table): void {
            $table->dropColumn(['ticket_prefix', 'reference_prefix', 'arrival_date', 'session_label']);
        });
    }
};
