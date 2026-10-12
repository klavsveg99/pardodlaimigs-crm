<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Apsveikumu opt-in tagad ir pēc noklusējuma IZSLĒGTS: klientam jābūt
 * atzīmētam, lai saņemtu apsveikumus. "Laimīgajiem" tas tiek ieslēgts
 * automātiski (skat. Client modeli), tāpēc esošajiem atzīmējam tos uzreiz.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->boolean('greetings_enabled')->default(false)->change();
        });

        DB::table('clients')->update([
            'greetings_enabled' => DB::raw("(status = 'laimigs')"),
        ]);
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table): void {
            $table->boolean('greetings_enabled')->default(true)->change();
        });
    }
};
