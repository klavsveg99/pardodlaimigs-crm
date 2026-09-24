<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Klienta statuss: "Aktīvs" (parasts klients) vai "Līdis" (potenciāls klients). */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('status', 30)->default('active')->index();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
