<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Phase A: merchant connector/affiliate/import configuration (§4, §20, §26).
        Schema::table('merchants', function (Blueprint $table) {
            $table->string('connector_type')->nullable()->after('slug');      // e.g. startech|daraz|ryans|generic
            $table->string('product_import_method')->default('csv')->after('connector_type'); // csv|feed|url|api
            $table->string('affiliate_link_method')->nullable()->after('product_import_method'); // manual|generator
            $table->boolean('affiliate_enabled')->default(false)->after('affiliate_link_method');
            $table->json('configuration')->nullable()->after('feed_config');  // generator url, category map, parser settings
        });
    }

    public function down(): void
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn(['connector_type', 'product_import_method', 'affiliate_link_method', 'affiliate_enabled', 'configuration']);
        });
    }
};
