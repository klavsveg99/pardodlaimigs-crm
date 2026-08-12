<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties_cache', function (Blueprint $t) {
            $t->unsignedBigInteger('id')->primary(); // == wp_posts.ID of property CPT
            $t->string('title');
            $t->string('slug');
            $t->string('status', 60)->nullable()->index();
            $t->unsignedBigInteger('price_cents')->nullable()->index();
            $t->string('currency', 3)->default('EUR');
            $t->unsignedSmallInteger('beds')->nullable();
            $t->unsignedSmallInteger('baths')->nullable();
            $t->decimal('size_m2', 10, 2)->nullable();
            $t->decimal('land_m2', 10, 2)->nullable();
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->string('country', 80)->nullable();
            $t->string('state', 120)->nullable();
            $t->string('city', 120)->nullable()->index();
            $t->string('neighborhood', 120)->nullable();
            $t->string('address')->nullable();
            $t->json('type_ids')->nullable();
            $t->json('feature_ids')->nullable();
            $t->json('label_ids')->nullable();
            $t->string('thumbnail_url', 500)->nullable();
            $t->json('gallery_urls')->nullable();
            $t->unsignedBigInteger('agent_wp_user_id')->nullable()->index();
            $t->unsignedBigInteger('agency_wp_term_id')->nullable();
            $t->string('wp_permalink', 500)->nullable();
            $t->timestamp('wp_updated_at')->nullable();
            $t->timestamp('cached_at')->useCurrent();
            $t->index(['status', 'cached_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties_cache');
    }
};
