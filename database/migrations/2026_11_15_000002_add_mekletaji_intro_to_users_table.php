<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marks that a user has seen the one-time CRM 2.0 / Meklētāji introduction.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('mekletaji_intro_seen_at')->nullable()->after('default_commission_percent');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('mekletaji_intro_seen_at');
        });
    }
};
