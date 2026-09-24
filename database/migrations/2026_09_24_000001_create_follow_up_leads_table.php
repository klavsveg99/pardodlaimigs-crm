<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Follow Up līdi" — prospective sellers who are not ready to start the
 * cooperation yet (inheritance, divorce, paperwork, …). A pre-cooperation
 * stage, not a lost client. Self-contained so it can be dropped later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('follow_up_leads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $t->foreignId('crm_property_id')->nullable()->constrained('crm_properties')->nullOnDelete();
            $t->string('property_address')->nullable();
            $t->date('conversation_started_at')->nullable();
            $t->string('reason', 100)->nullable();
            $t->text('reason_note')->nullable();
            $t->date('target_start_at')->nullable();
            $t->date('next_contact_at')->nullable()->index();
            $t->unsignedSmallInteger('cadence_days')->default(7);
            $t->timestamp('last_contacted_at')->nullable();
            $t->unsignedInteger('contact_count')->default(0);
            $t->string('status', 30)->default('active')->index();
            $t->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->text('notes')->nullable();
            // Keep the origin Pieteikums linked for traceability; nullable and
            // indexed only, so removing the feature needs no data surgery.
            $t->unsignedBigInteger('source_wpform_entry_id')->nullable()->index();
            $t->timestamps();
            $t->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('follow_up_leads');
    }
};
