<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // §38 landing pages — dynamic content pages, reusable across placements (§37).
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->string('status')->default('draft')->index(); // draft|published
            $table->json('sections')->nullable();                // ordered section config (Hero, CTA, FAQ…)
            $table->timestamp('published_at')->nullable()->index();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('canonical_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('landing_page_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['landing_page_id', 'product_id']);
        });

        Schema::create('landing_page_comparison', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('comparison_id')->constrained()->cascadeOnDelete()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['landing_page_id', 'comparison_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_comparison');
        Schema::dropIfExists('landing_page_product');
        Schema::dropIfExists('landing_pages');
    }
};
