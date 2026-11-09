<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('portal', 20); // ss, city24
            $table->string('category', 20); // land, houses, apartments, commercial
            $table->string('location')->nullable();
            $table->unsignedInteger('price_min')->nullable();
            $table->unsignedInteger('price_max')->nullable();
            $table->unsignedInteger('area_min')->nullable();
            $table->unsignedInteger('area_max')->nullable();
            $table->string('keywords')->nullable();
            $table->unsignedSmallInteger('max_results')->default(50);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'portal']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_searches');
    }
};
