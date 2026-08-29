<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->string('path', 500)->index();          // internal path only, never external
            $table->string('referrer_page', 500)->nullable(); // internal referrer path (privacy-filtered below)
            $table->string('ip_hash', 64)->nullable()->index(); // salted sha256, never raw IP
            $table->string('user_agent_family', 40)->nullable(); // coarse family only ('mobile'/'desktop')
            $table->timestamp('viewed_at')->index();
            $table->index(['viewed_at', 'ip_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
