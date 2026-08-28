<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // §19: affiliate info is separate from the normal merchant product URL.
        // One affiliate offer per merchant product (offer), owned by a canonical product.
        Schema::create('affiliate_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete()->index(); // the merchant product/listing
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete()->index();
            $table->text('normal_product_url')->nullable();
            $table->text('affiliate_url')->nullable();        // generated or manually pasted (§21)
            $table->string('tracking_identifier')->nullable();
            $table->decimal('commission_rate', 10, 2)->nullable();
            $table->string('commission_type')->nullable();    // percent|fixed
            $table->boolean('commission_eligible')->default(false)->index();
            $table->string('status')->default('pending')->index(); // pending|generating|generated|failed|manual|invalid|inactive
            $table->string('generation_method')->nullable();  // manual|automated
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->text('last_error')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['offer_id']);
        });

        // §20 Manual/Automated generation history for every affiliate offer.
        Schema::create('affiliate_link_generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_offer_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete()->index();
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete()->index();
            $table->string('method');                         // manual|automated
            $table->string('status')->default('queued')->index(); // queued|processing|success|failed|invalid
            $table->text('input_url')->nullable();            // product URL pasted into generator (§21)
            $table->text('generated_url')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // Backfill: existing offers carry affiliate_url; promote into affiliate_offers as manual.
        DB::table('offers')
            ->select('id', 'product_id', 'merchant_id', 'external_url', 'affiliate_url')
            ->orderBy('id')
            ->chunkById(200, function ($offers): void {
                foreach ($offers as $offer) {
                    $url = trim((string) $offer->affiliate_url);
                    if ($url === '') {
                        continue;
                    }

                    DB::table('affiliate_offers')->insert([
                        'offer_id' => $offer->id,
                        'product_id' => $offer->product_id,
                        'merchant_id' => $offer->merchant_id,
                        'normal_product_url' => $offer->external_url ?: null,
                        'affiliate_url' => $url,
                        'status' => 'manual',
                        'generation_method' => 'manual',
                        'generated_at' => now(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_link_generations');
        Schema::dropIfExists('affiliate_offers');
    }
};
