<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dzimšanas datums (no personas koda vai manuāli), pēdējā apsveikšanas
     * reize, kā arī juridiskajiem dokumentiem nepieciešamā adrese un
     * bankas konta numurs.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->date('birth_date')->nullable()->after('personas_kods');
            $table->date('birthday_greeted_at')->nullable()->after('birth_date');
            $table->string('address', 255)->nullable()->after('birthday_greeted_at');
            $table->string('bank_account', 64)->nullable()->after('address');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['birth_date', 'birthday_greeted_at', 'address', 'bank_account']);
        });
    }
};
