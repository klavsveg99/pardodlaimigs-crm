<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Darījumi (deals) were removed from the CRM entirely — the property
     * itself carries the sale lifecycle (status, gala cena, komisija,
     * sold_at). Drop the table and its references.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $t) {
            if (Schema::hasColumn('tasks', 'deal_id')) {
                try {
                    $t->dropConstrainedForeignId('deal_id');
                } catch (\Throwable) {
                    $t->dropColumn('deal_id');
                }
            }
        });

        Schema::table('activities', function (Blueprint $t) {
            // Composite index references deal_id — must go before the column.
            try {
                $t->dropIndex('activities_deal_id_created_at_index');
            } catch (\Throwable) {
            }

            if (Schema::hasColumn('activities', 'deal_id')) {
                $t->dropColumn('deal_id');
            }
        });

        Schema::dropIfExists('deals');
    }

    public function down(): void
    {
        // Deals are gone for good — nothing to restore.
    }
};
