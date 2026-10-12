<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Administratora rediģējamie automātisko e-pastu teksti (Iestatījumi).
 * Viena rinda ar JSON: apsveikumi, uzdevuma paziņojums, kavētā uzdevuma
 * atgādinājums. Noklusējumi tiek ņemti no EmailSetting::defaults().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_settings', function (Blueprint $table): void {
            $table->id();
            $table->json('data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_settings');
    }
};
