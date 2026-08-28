<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('comparison_offer', function (Blueprint $table) {
            // §36 admin manual override: flag which merchant offer is the
            // "Best Overall Deal" for its product (heuristic used when unset).
            $table->boolean('is_best_deal')->default(false)->after('is_hidden');
        });
    }

    public function down(): void
    {
        Schema::table('comparison_offer', function (Blueprint $table) {
            $table->dropColumn('is_best_deal');
        });
    }
};
