<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Juridisko personu papildu kontaktpersonas: pilnvarotā persona (kas
     * paraksta darījumu) un kontaktpersona (ar ko sazināties).
     * Struktūra: {name, personas_kods, address, phone, email}.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->json('legal_representative')->nullable()->after('person_type');
            $table->json('contact_person')->nullable()->after('legal_representative');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['legal_representative', 'contact_person']);
        });
    }
};
