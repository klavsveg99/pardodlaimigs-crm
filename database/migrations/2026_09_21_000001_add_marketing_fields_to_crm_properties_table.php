<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sales/partnership tracking + the "price and status unchanged" reminder.
     *
     * price_status_changed_at is maintained by the CrmProperty model and is
     * the timestamp the 45-day stale-listing notification counts from.
     */
    public function up(): void
    {
        Schema::table('crm_properties', function (Blueprint $table): void {
            $table->timestamp('sale_started_at')->nullable()->after('status');
            $table->unsignedTinyInteger('partnership_months')->nullable()->after('sale_started_at');
            $table->timestamp('price_status_changed_at')->nullable()->after('partnership_months');
            $table->timestamp('stale_notice_dismissed_at')->nullable()->after('price_status_changed_at');
        });

        // Existing listings start counting from their last update.
        DB::table('crm_properties')->update([
            'price_status_changed_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('crm_properties', function (Blueprint $table): void {
            $table->dropColumn([
                'sale_started_at',
                'partnership_months',
                'price_status_changed_at',
                'stale_notice_dismissed_at',
            ]);
        });
    }
};
