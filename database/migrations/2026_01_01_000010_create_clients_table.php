<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $t) {
            $t->id();
            $t->string('name')->index();
            $t->string('phone', 40)->nullable()->index();
            $t->string('email')->nullable()->index();
            $t->string('source', 100)->nullable();
            $t->timestamp('gdpr_consent_at')->nullable();
            $t->timestamp('gdpr_erased_at')->nullable();
            $t->text('notes_md')->nullable();
            $t->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['gdpr_erased_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
