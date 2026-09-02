<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // §16: source-aware imports — a batch belongs to a merchant and may be URL-driven.
        Schema::table('import_batches', function (Blueprint $table) {
            $table->foreignId('merchant_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('source_url')->nullable()->after('filename');    // URL scraper source (§3/§31)
            $table->string('source_type')->default('csv')->after('type');   // csv|json|url|api
        });

        // Per-sourced-item staging: raw data, normalization result, match target (§31/§32, §40).
        Schema::create('import_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete();
            $table->string('source_identifier')->nullable();   // merchant product id / source row key
            $table->json('raw_data')->nullable();              // raw scraped/parsed payload
            $table->json('normalized_data')->nullable();       // after normalization (§56)
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('match_type')->nullable();          // exact(sku)|gtin|name|none
            $table->string('status')->default('pending')->index(); // pending|matched|new|duplicate|updated|skipped|error
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_items');

        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('merchant_id');
            $table->dropColumn(['source_url', 'source_type']);
        });
    }
};
