<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A viewing may exist without a tied property (e.g. office/phone viewing),
        // and test data should never fabricate a properties_cache row as a crutch.
        // RELATE to existing properties when present; otherwise leave it unset.
        Schema::table('viewings', function (Blueprint $table): void {
            $table->unsignedBigInteger('property_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('viewings', function (Blueprint $table): void {
            $table->unsignedBigInteger('property_id')->nullable(false)->change();
        });
    }
};
