<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->enum('lead_source', ['internal', 'external'])->default('internal')->index()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->dropColumn('lead_source');
        });
    }
};
