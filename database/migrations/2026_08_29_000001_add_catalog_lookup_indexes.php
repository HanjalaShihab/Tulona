<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill common FK lookup indexes that the earliest tables were shipped
 * without. Cheap to create now; saved the live catalog from O(n) full scans
 * on category/brand/merchant/image filter paths.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('brand_id');
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->index('merchant_id');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->index('product_id');
        });

        Schema::table('price_drop_events', function (Blueprint $table) {
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['brand_id']);
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex(['merchant_id']);
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
        });

        Schema::table('price_drop_events', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
        });
    }
};
