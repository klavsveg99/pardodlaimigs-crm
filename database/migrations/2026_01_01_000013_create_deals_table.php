<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->unsignedBigInteger('property_id')->nullable();
            $t->enum('stage', [
                'lead',
                'viewing_scheduled',
                'offer',
                'reserved',
                'closed_won',
                'closed_lost',
            ])->default('lead')->index();
            $t->unsignedBigInteger('value_cents')->nullable();
            $t->string('currency', 3)->default('EUR');
            $t->date('expected_close_date')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->foreign('property_id')->references('id')->on('properties_cache')->nullOnDelete();
            $t->index(['stage', 'owner_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
