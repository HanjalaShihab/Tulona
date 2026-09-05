<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type')->default('guide')->index(); // guide|review
            $table->text('excerpt')->nullable();
            $table->longText('content');                 // HTML content
            $table->string('featured_image')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author')->default('Editorial Team');
            $table->string('status')->default('draft')->index(); // draft|published
            $table->timestamp('published_at')->nullable()->index();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_image')->nullable();
            $table->json('faqs')->nullable();            // [{question, answer}] → FAQPage schema
            $table->json('selection_criteria')->nullable(); // guides: how we picked
            $table->timestamps();
        });

        Schema::create('article_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->text('blurb')->nullable();           // "Why we picked it"
            $table->string('pick_label')->nullable();    // Best Overall / Budget Pick...
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['article_id', 'product_id']);
        });

        Schema::create('settings', function (Blueprint $table) { // homepage sections/banners managed by admin (§35)
            $table->id();
            $table->string('key')->unique();
            $table->json('value')->nullable();
            $table->timestamps();
        });

        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('type')->default('csv');      // csv|json
            $table->string('status')->default('pending')->index(); // pending|validated|failed|processing|completed
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('import_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->cascadeOnDelete()->index();
            $table->unsignedInteger('row_number')->nullable();
            $table->string('field')->nullable();
            $table->text('message');
            $table->string('severity')->default('error'); // error|warning
            $table->timestamps();
        });

        Schema::create('sync_logs', function (Blueprint $table) {   // §26 + §65
            $table->id();
            $table->foreignId('merchant_id')->constrained()->cascadeOnDelete()->index();
            $table->string('status');                    // running|success|failed
            $table->unsignedInteger('items_updated')->default(0);
            $table->unsignedInteger('items_failed')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {  // §56 admin audit trail
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('sync_logs');
        Schema::dropIfExists('import_errors');
        Schema::dropIfExists('import_batches');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('article_product');
        Schema::dropIfExists('articles');
    }
};
