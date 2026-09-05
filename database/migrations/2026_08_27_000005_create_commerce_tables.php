<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One product → many merchant offers (§23).
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete()->index();
            $table->string('external_product_id')->nullable();
            $table->text('external_url')->nullable();
            $table->text('affiliate_url');
            $table->decimal('current_price', 14, 2)->nullable()->index(); // null = price unavailable, shown honestly
            $table->decimal('original_price', 14, 2)->nullable();          // for real discount calc only
            $table->char('currency', 3)->default('BDT');
            $table->string('availability')->default('unknown')->index(); // in_stock|out_of_stock|preorder|unknown
            $table->string('shipping_info')->nullable();
            $table->timestamp('deal_expires_at')->nullable();             // only when source provides it
            $table->string('source')->default('manual');                  // manual|api|feed|import
            $table->string('status')->default('active')->index();
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'merchant_id']);
        });

        // Append-only history; duplicates avoided by service (§27).
        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete()->index();
            $table->decimal('price', 14, 2);
            $table->char('currency', 3)->default('BDT');
            $table->timestamp('recorded_at')->index();
            $table->index(['offer_id', 'recorded_at']);
        });

        Schema::create('price_drop_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->decimal('previous_price', 14, 2);
            $table->decimal('current_price', 14, 2);
            $table->decimal('drop_amount', 14, 2);
            $table->decimal('drop_percent', 6, 2);
            $table->char('currency', 3)->default('BDT');
            $table->timestamp('occurred_at')->index();
        });

        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->index();
            $table->string('label')->nullable();
            $table->string('source')->default('price_drop'); // price_drop|merchant_promo
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // Anonymous click tracking — hashed IP, no PII (§5, §29).
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->index();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete()->index();
            $table->string('referrer_page')->nullable();   // internal landing page path
            $table->string('ip_hash', 64)->nullable();     // salted hash, never raw IP
            $table->string('user_agent_family', 40)->nullable(); // coarse family only
            $table->timestamp('clicked_at')->index();
            $table->index(['product_id', 'clicked_at']);
            $table->index(['merchant_id', 'clicked_at']);
            $table->date('clicked_on')->index();
        });

        // Commission data imported from networks — never derived from clicks (§59).
        Schema::create('affiliate_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('network')->nullable();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_order_ref')->nullable();
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->char('currency', 3)->default('BDT');
            $table->string('status')->default('pending'); // pending|approved|declined
            $table->timestamp('converted_at')->nullable()->index();
            $table->timestamp('imported_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_conversions');
        Schema::dropIfExists('clicks');
        Schema::dropIfExists('deals');
        Schema::dropIfExists('price_drop_events');
        Schema::dropIfExists('price_history');
        Schema::dropIfExists('offers');
    }
};
