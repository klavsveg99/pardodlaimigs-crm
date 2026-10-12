<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Noņem atsevišķo "mārketinga piekrišanu". Klienta piekrišanu tagad nosaka
 * viens ķeksītis clients.greetings_enabled (apsveikumi un paziņojumi, GDPR).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn('marketing_consent');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->boolean('marketing_consent')->default(false)->after('gdpr_consent_at');
        });
    }
};
