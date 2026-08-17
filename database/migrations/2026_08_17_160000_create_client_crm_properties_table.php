<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_crm_properties', function (Blueprint $table) {
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('crm_property_id')->constrained('crm_properties')->cascadeOnDelete();
            $table->enum('relation', ['buyer', 'seller', 'tenant', 'landlord', 'interested', 'contacted']);
            $table->text('notes_md')->nullable();
            $table->timestamps();

            $table->primary(['client_id', 'crm_property_id', 'relation']);
            $table->index('crm_property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_crm_properties');
    }
};
