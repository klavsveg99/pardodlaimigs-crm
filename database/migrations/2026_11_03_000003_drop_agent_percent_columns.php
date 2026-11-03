<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('crm_properties', 'agent_percent')) {
            Schema::table('crm_properties', function (Blueprint $table): void {
                $table->dropColumn('agent_percent');
            });
        }

        if (Schema::hasColumn('users', 'default_commission_percent')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('default_commission_percent');
            });
        }
    }

    public function down(): void
    {
        // Kolonnas tika ieviestas un pēc tam aizstātas ar aprēķinu no
        // komisijas un gala cenas, tāpēc atgriešana nav nepieciešama.
    }
};
