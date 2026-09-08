<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $t) {
            $t->id();
            $t->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('client_id')->nullable()->constrained('clients')->cascadeOnDelete();
            $t->foreignId('property_id')->nullable()->constrained('crm_properties')->nullOnDelete();
            $t->string('type', 60)->index(); // created, updated, note_added, viewing_booked, email_sent, ...
            $t->json('payload')->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['client_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
