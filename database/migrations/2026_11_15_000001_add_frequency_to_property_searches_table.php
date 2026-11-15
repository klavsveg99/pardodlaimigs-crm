<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How often a saved search re-checks the portals on its own. Existing
 * searches keep the previous behaviour of one automatic run per day.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_searches', function (Blueprint $table) {
            $table->string('frequency', 20)->default('daily')->after('is_active');
            $table->index('frequency');
        });
    }

    public function down(): void
    {
        Schema::table('property_searches', function (Blueprint $table) {
            $table->dropIndex(['frequency']);
            $table->dropColumn('frequency');
        });
    }
};
