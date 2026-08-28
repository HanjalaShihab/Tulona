<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // §7 / §39: publication states draft|pending_review|published|archived.
        // Existing "active" products are live → map to "published".
        DB::table('products')->where('status', 'active')->update(['status' => 'published']);

        Schema::table('products', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
        });

        DB::table('products')->where('status', 'published')->update(['status' => 'active']);
    }
};
