<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Teksti vairs neizmanto īskodus (piem. {name}) un kavētā uzdevuma
 * atgādinājums ir noņemts, tāpēc iepriekš saglabāto rindu dzēšam: nākamajā
 * piekļuvē EmailSetting::current() to izveidos no jaunajiem noklusējumiem.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('email_settings')) {
            DB::table('email_settings')->delete();
        }
    }

    public function down(): void
    {
        // Neatgriežam vecos tekstus.
    }
};
