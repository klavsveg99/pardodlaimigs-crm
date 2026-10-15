<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Līda turpmākā saziņa: datums, kad jāsazinās ar klientu, un piezīme par
 * situāciju (piem. "īpašums izīrēts uz gadu"). Abi lauki ir redzami tikai
 * klientiem ar statusu "Līds".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->date('follow_up_at')->nullable()->after('status');
            $table->text('follow_up_note')->nullable()->after('follow_up_at');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn(['follow_up_at', 'follow_up_note']);
        });
    }
};
