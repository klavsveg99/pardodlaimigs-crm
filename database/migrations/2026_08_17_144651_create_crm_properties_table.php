<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crm_properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('price_cents')->default(0);
            $table->decimal('price_eur', 12, 2)->default(0);
            $table->string('currency', 3)->default('EUR');
            $table->string('category')->nullable();
            $table->string('status')->default('draft'); // draft, published, expired, hidden, sold
            $table->unsignedSmallInteger('beds')->nullable();
            $table->unsignedSmallInteger('baths')->nullable();
            $table->unsignedInteger('size_m2')->nullable();
            $table->unsignedInteger('land_m2')->nullable();
            $table->string('kadastra_nr')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_properties');
    }
};
