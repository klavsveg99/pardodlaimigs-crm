<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraped_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_search_id')->nullable()->constrained('property_searches')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('portal', 20);
            $table->string('external_id', 191);
            $table->text('url');
            $table->text('title')->nullable();
            $table->decimal('price_eur', 12, 2)->nullable();
            $table->unsignedInteger('area_m2')->nullable();
            $table->unsignedInteger('land_m2')->nullable();
            $table->string('location')->nullable();
            $table->string('address')->nullable();
            $table->text('image_url')->nullable();
            $table->timestamp('listed_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('status', 20)->default('new'); // new, seen, dismissed
            $table->foreignId('duplicate_of_id')->nullable()->constrained('scraped_listings')->nullOnDelete();
            $table->string('fingerprint', 40)->nullable();
            $table->timestamps();

            // One row per agent and ad. Different agents keep their own inbox.
            $table->unique(['user_id', 'portal', 'external_id']);
            $table->index(['user_id', 'status']);
            $table->index('fingerprint');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraped_listings');
    }
};
