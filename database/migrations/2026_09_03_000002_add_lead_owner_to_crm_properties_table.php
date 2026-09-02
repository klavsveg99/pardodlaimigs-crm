<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->string('lead_owner', 255)->nullable()->after('lead_source');
        });
    }

    public function down(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->dropColumn('lead_owner');
        });
    }
};
