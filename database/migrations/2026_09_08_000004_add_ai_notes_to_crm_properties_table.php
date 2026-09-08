<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_properties', function (Blueprint $table): void {
            $table->json('ai_notes')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('crm_properties', function (Blueprint $table): void {
            $table->dropColumn('ai_notes');
        });
    }
};
