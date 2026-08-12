<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viewings', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('property_id');
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->foreignId('agent_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->dateTime('scheduled_at')->index();
            $t->unsignedSmallInteger('duration_min')->default(30);
            $t->enum('status', ['scheduled', 'done', 'cancelled', 'no_show'])->default('scheduled')->index();
            $t->text('notes_md')->nullable();
            $t->timestamps();
            $t->foreign('property_id')->references('id')->on('properties_cache')->cascadeOnDelete();
            $t->index(['agent_user_id', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('viewings');
    }
};
