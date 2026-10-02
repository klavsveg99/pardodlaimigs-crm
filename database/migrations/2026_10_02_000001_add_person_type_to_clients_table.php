<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Klienta persona darījumā: fiziska (privātpersona) vai juridiska (SIA u.c.).
     * Jurista dokumentā no tā atkarīgi lauku nosaukumi un e-pasta saturs.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('person_type', 20)->default('fiziska')->after('name')->index();
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['person_type']);
            $table->dropColumn('person_type');
        });
    }
};
