<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // §16: admin-chosen category applied to all rows of a URL scrape on import.
        Schema::table('import_batches', function (Blueprint $table) {
            $table->string('category_slug')->nullable()->after('source_url')->index();
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn('category_slug');
        });
    }
};
