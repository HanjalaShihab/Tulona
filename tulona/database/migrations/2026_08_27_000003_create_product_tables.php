<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Category-specific filterable attributes (GPU VRAM, phone RAM, etc.)
        Schema::create('attribute_definitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete(); // null = global
            $table->string('name');          // "VRAM"
            $table->string('key')->index();  // "vram"
            $table->string('data_type')->default('string'); // string|number|boolean|enum
            $table->string('unit')->nullable();             // GB, inch, mAh...
            $table->json('options')->nullable();            // for enum type
            $table->boolean('is_filterable')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Products are decoupled from merchants — Offers connect them (§51).
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete()->index();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete()->index();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->nullable()->index();
            $table->string('model_number')->nullable()->index();
            $table->string('gtin')->nullable()->index();     // UPC/EAN for product matching (§24)
            $table->string('product_type')->default('physical'); // physical|digital
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->text('summary_editorial')->nullable();   // editorial summary on detail page
            $table->json('pros')->nullable();
            $table->json('cons')->nullable();
            $table->decimal('rating', 3, 1)->nullable();     // editorial/aggregated rating; never fabricated
            $table->string('pricing_model')->nullable();     // free|freemium|subscription|one_time (software/AI)
            $table->boolean('has_free_plan')->default(false);
            $table->json('platforms')->nullable();           // web/windows/macos/android/ios
            $table->boolean('is_featured')->default(false)->index();
            $table->boolean('is_trending')->default(false)->index();
            $table->boolean('is_editors_pick')->default(false);
            $table->boolean('is_best_value')->default(false);
            $table->boolean('is_budget_pick')->default(false);
            $table->boolean('is_premium_pick')->default(false);
            $table->unsignedBigInteger('clicks_count')->default(0); // cached aggregate for ranking (§49)
            $table->decimal('popularity_score', 8, 2)->default(0);
            $table->string('status')->default('active')->index();    // active|archived
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->index();
            $table->string('path');                  // storage-relative path OR permitted remote URL
            $table->string('alt_text')->nullable();
            $table->boolean('is_main')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('product_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('attribute_definition_id')->constrained()->cascadeOnDelete();
            $table->string('value_text')->nullable()->index();
            $table->decimal('value_number', 12, 3)->nullable()->index();
            $table->boolean('value_boolean')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'attribute_definition_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('attribute_definitions');
    }
};
