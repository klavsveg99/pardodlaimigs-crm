<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-only "featured" flag for properties. Featured properties are pinned
 * above the rest on the public site when the corresponding sort mode is
 * selected in Settings -> Īpašumi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->boolean('is_featured')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('crm_properties', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
};
