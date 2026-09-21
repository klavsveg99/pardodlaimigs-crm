<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Dedicated "photo" account: may only create properties and edit their
     * title + gallery. Everything else is locked down in the UI layer.
     */
    public function up(): void
    {
        if (DB::table('users')->where('email', 'photo@pardodlaimigs.lv')->exists()) {
            return;
        }

        DB::table('users')->insert([
            'name' => 'Photo',
            'email' => 'photo@pardodlaimigs.lv',
            'role' => 'photo',
            'password' => Hash::make('Photo123!'),
            'slug' => 'photo',
            'calendar_token' => bin2hex(random_bytes(32)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'photo@pardodlaimigs.lv')->delete();
    }
};
