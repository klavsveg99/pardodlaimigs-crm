<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Persist "Nosūtīts" for the manual task notification buttons — previously
     * the sent state only survived the redirect that followed the POST.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->timestamp('agent_notified_at')->nullable()->after('completed_at');
            $table->timestamp('izpilditajs_notified_at')->nullable()->after('agent_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn(['agent_notified_at', 'izpilditajs_notified_at']);
        });
    }
};
