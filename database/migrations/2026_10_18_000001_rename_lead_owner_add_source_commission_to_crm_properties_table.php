<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ārējā pieteikuma avota lauki: iepriekšējais "lead_owner" (rādīts kā
 * "Atbildīgais aģents") kļūst par "Avota nosaukums", un tam pievienojas
 * "Avota komisija (%)".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->renameColumn('lead_owner', 'lead_source_name');
        });

        Schema::table('crm_properties', function (Blueprint $table) {
            $table->decimal('lead_source_commission', 5, 2)->nullable()->after('lead_source_name');
        });
    }

    public function down(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->dropColumn('lead_source_commission');
        });

        Schema::table('crm_properties', function (Blueprint $table) {
            $table->renameColumn('lead_source_name', 'lead_owner');
        });
    }
};
