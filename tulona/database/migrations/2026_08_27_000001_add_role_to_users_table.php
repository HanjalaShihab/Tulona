<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-only platform: public visitors are anonymous (Build.md §6).
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('analyst')->index(); // super_admin|content_manager|product_manager|analyst
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active']);
        });
    }
};
