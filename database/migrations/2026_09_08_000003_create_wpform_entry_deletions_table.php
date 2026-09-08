<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tombstones for wpform entries deleted in the CRM: the periodic WP
        // sync must never re-create (or resurrect) an entry the user deleted.
        Schema::create('wpform_entry_deletions', function (Blueprint $table): void {
            $table->string('external_id')->primary();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wpform_entry_deletions');
    }
};
