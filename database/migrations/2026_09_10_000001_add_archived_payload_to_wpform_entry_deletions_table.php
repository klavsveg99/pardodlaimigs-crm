<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Extend tombstones with a full archived snapshot: when a WPForms
        // entry is deleted in the CRM, its whole payload is kept locally so
        // the data is never lost even though WordPress still holds (or has
        // removed) the original.
        Schema::table('wpform_entry_deletions', function (Blueprint $table): void {
            $table->unsignedBigInteger('entry_id')->nullable();
            $table->unsignedBigInteger('form_id')->nullable();
            $table->string('form_name')->nullable();
            $table->unsignedBigInteger('client_id')->nullable();
            $table->text('fields')->nullable();
            $table->timestamp('entry_created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('wpform_entry_deletions', function (Blueprint $table): void {
            $table->dropColumn(['entry_id', 'form_id', 'form_name', 'client_id', 'fields', 'entry_created_at']);
        });
    }
};
