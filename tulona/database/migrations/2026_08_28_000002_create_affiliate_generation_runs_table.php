<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_generation_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('queued'); // queued, processing, completed, failed
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('generated')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'merchant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_generation_runs');
    }
};
