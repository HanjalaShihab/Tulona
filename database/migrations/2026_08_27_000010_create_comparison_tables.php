<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // §29–37: Comparison — reuse canonical products/offers, never duplicate product records.
        Schema::create('comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('introduction')->nullable();
            $table->longText('description')->nullable();
            $table->text('verdict')->nullable();           // §36 editorial verdict
            $table->text('notes')->nullable();
            $table->string('cta_text')->nullable();        // button label
            $table->string('status')->default('draft')->index(); // draft|published|archived (§37)
            $table->boolean('featured')->default(false)->index();
            $table->json('merchant_order')->nullable();    // render order of merchants (§34)
            $table->json('specifications_shown')->nullable(); // which spec keys to display (§34)
            $table->timestamp('published_at')->nullable()->index();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('canonical_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Products in a comparison — reuses canonical Product rows (§29).
        Schema::create('comparison_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comparison_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->text('editorial_notes')->nullable();   // why we included it
            $table->string('pick_label')->nullable();      // Best Price / Best Deal override (§36)
            $table->unique(['comparison_id', 'product_id']);
        });

        // Per-merchant offer participation + admin overrides (§34, §35).
        Schema::create('comparison_offer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comparison_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete()->index();
            $table->boolean('is_hidden')->default(false);          // hide merchant offer (§34)
            $table->decimal('override_price', 14, 2)->nullable();   // manual override (§34)
            $table->string('override_availability')->nullable();
            $table->string('override_warranty')->nullable();
            $table->string('override_shipping')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['comparison_id', 'offer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comparison_offer');
        Schema::dropIfExists('comparison_product');
        Schema::dropIfExists('comparisons');
    }
};
