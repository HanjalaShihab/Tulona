<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_networks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('website_url')->nullable();
            $table->json('api_config')->nullable();   // official API / feed configuration (no secrets here → env)
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Generic merchant system — admins add merchants without core changes (§4).
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_network_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo_path')->nullable();
            $table->text('description')->nullable();
            $table->string('website_url')->nullable();
            $table->string('country')->default('BD')->index();
            $table->string('region')->nullable();
            $table->json('currencies')->default('["BDT"]');
            $table->string('base_affiliate_url')->nullable();
            $table->string('tracking_template')->nullable();  // e.g. "{affiliate_url}?subid={click}"
            $table->json('feed_config')->nullable();          // official feed/API settings for sync (§26)
            $table->string('commission_note')->nullable();
            $table->string('status')->default('active')->index(); // active|inactive
            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status')->nullable();        // success|failed
            $table->text('terms_notes')->nullable();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
        Schema::dropIfExists('affiliate_networks');
    }
};
