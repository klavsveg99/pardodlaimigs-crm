<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The original enum() created a stale CHECK constraint (lead/viewing_scheduled/offer/
        // reserved/closed_won/closed_lost) that was only relaxed on MySQL. On SQLite the
        // current app stages ('jauns' ... 'pardots') can never be stored, so deal creation
        // silently fails. Relax the column to match the app model.
        Schema::table('deals', function (Blueprint $table): void {
            $table->string('stage', 60)->default('jauns')->change();
        });
    }

    public function down(): void
    {
        Schema::table('deals', function (Blueprint $table): void {
            $table->string('stage', 60)->default('lead')->change();
        });
    }
};
