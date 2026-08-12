<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('wpform_entries')
            ->whereNull('status')
            ->orWhere('status', '!=', 'new')
            ->update(['status' => 'new']);
    }

    public function down(): void {}
};
