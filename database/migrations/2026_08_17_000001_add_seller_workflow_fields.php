<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->string('personas_kods', 32)->nullable()->after('email');
            $table->boolean('marketing_consent')->default(false)->after('gdpr_consent_at');
        });

        Schema::table('deals', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('id');
            $table->decimal('value_eur', 14, 2)->nullable()->after('stage');
        });

        Schema::table('properties_cache', function (Blueprint $table): void {
            $table->string('kadastra_nr', 120)->nullable()->index()->after('address');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE deals MODIFY stage VARCHAR(60) NOT NULL DEFAULT 'jauns'");
            DB::statement("UPDATE deals SET stage = CASE stage
                WHEN 'lead' THEN 'jauns'
                WHEN 'viewing_scheduled' THEN 'pirma_tiksanas'
                WHEN 'offer' THEN 'noslegta_sadarbiba'
                WHEN 'reserved' THEN 'dokumentu_saskanosana'
                WHEN 'closed_won' THEN 'pardots'
                WHEN 'closed_lost' THEN 'jauns'
                ELSE stage END");
            DB::statement('UPDATE deals SET value_eur = value_cents / 100 WHERE value_eur IS NULL AND value_cents IS NOT NULL');
        }
    }

    public function down(): void
    {
        Schema::table('properties_cache', fn (Blueprint $table) => $table->dropColumn('kadastra_nr'));
        Schema::table('deals', function (Blueprint $table): void {
            $table->dropColumn(['title', 'value_eur']);
        });
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn(['personas_kods', 'marketing_consent']);
        });
    }
};
