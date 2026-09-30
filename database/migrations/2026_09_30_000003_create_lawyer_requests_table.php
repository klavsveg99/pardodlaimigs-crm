<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Juristam nosūtīto dokumentu pieprasījumu vēsture. */
    public function up(): void
    {
        Schema::create('lawyer_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crm_property_id')->constrained('crm_properties')->cascadeOnDelete();
            $table->foreignId('izpilditajs_id')->nullable()->constrained('izpilditajs')->nullOnDelete();
            $table->string('document_type', 60);
            $table->string('recipient_email', 255);
            $table->string('subject', 255);
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('sent');
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['crm_property_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lawyer_requests');
    }
};
