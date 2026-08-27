<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('counter_closure_requests', function (Blueprint $table): void {
            $table->text('reason')->change();
        });
    }

    public function down(): void
    {
        Schema::table('counter_closure_requests', function (Blueprint $table): void {
            $table->string('reason', 100)->change();
        });
    }
};
