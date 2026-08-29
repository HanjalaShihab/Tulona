<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Upload CSV → review and post" drafts: each uploaded CSV row becomes an
        // editable draft the admin reviews and posts one-by-one (Product Generator).
        Schema::create('product_drafts', function (Blueprint $table) {
            $table->id();
            $table->json('data')->nullable();                 // parsed CSV row (canonical fields)
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete()->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft')->index(); // draft|posted|errors
            $table->string('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_drafts');
    }
};
