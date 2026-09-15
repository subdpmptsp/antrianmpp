<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_integration_health_checks', function (Blueprint $table): void {
            $table->id();
            $table->string('integration', 30)->index();
            $table->boolean('successful');
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('error_code', 60)->nullable();
            $table->string('message', 500)->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['integration', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_integration_health_checks');
    }
};
