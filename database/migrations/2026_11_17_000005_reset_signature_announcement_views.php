<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Show the updated e-mail signature announcement to everyone again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('feature_announcement_views')) {
            DB::table('feature_announcement_views')
                ->where('feature_key', 'signature')
                ->delete();
        }
    }

    public function down(): void
    {
        // Dismissed state is not restored.
    }
};
