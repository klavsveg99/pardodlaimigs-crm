<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties_cache', function (Blueprint $t) {
            $t->string('category', 120)->nullable()->after('status')->index();
        });
    }

    public function down(): void
    {
        Schema::table('properties_cache', function (Blueprint $t) {
            $t->dropColumn('category');
        });
    }
};
