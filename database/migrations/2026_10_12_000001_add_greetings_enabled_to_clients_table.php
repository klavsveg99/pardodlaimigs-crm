<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-client atļauja automātiskiem apsveikumiem (dzimšanas dienā un svētkos).
 * Pēc noklusējuma ieslēgta; klientu var atslēgt klienta kartītē.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->boolean('greetings_enabled')->default(true)->after('marketing_consent');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->dropColumn('greetings_enabled');
        });
    }
};
