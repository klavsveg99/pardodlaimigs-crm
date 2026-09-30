<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manuāli aizpildāmie juridiskie dati (līguma datumi/vieta, roknauda,
     * maksājumu grafiks, pilnvarotā persona u.c.) pirms nosūtīšanas
     * juristam. Struktūra mainās līdz ar dokumentu veidiem, tāpēc JSON.
     */
    public function up(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->json('legal_data')->nullable()->after('ai_result');
        });
    }

    public function down(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->dropColumn('legal_data');
        });
    }
};
