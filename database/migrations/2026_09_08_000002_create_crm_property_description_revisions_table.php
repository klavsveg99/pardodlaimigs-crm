<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_property_description_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('crm_property_id')->constrained('crm_properties')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['crm_property_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_property_description_revisions');
    }
};
