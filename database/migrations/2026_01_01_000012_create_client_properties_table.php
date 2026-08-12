<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_properties', function (Blueprint $t) {
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->unsignedBigInteger('property_id');
            $t->enum('relation', ['buyer', 'seller', 'tenant', 'landlord', 'interested', 'contacted']);
            $t->text('notes_md')->nullable();
            $t->timestamps();
            $t->primary(['client_id', 'property_id', 'relation']);
            $t->foreign('property_id')->references('id')->on('properties_cache')->cascadeOnDelete();
            $t->index(['property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_properties');
    }
};
