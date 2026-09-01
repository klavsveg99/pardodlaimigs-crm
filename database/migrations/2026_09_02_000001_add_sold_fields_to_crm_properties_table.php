<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->decimal('final_price_eur', 12, 2)->nullable()->after('price_eur');
            $table->decimal('commission_eur', 12, 2)->nullable()->after('final_price_eur');
        });
    }

    public function down(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->dropColumn(['final_price_eur', 'commission_eur']);
        });
    }
};
