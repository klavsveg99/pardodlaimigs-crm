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
            $table->decimal('agent_percent', 5, 2)->nullable()->after('commission_eur');
        });
    }

    public function down(): void
    {
        Schema::table('crm_properties', function (Blueprint $table): void {
            $table->dropColumn('agent_percent');
        });
    }
};
