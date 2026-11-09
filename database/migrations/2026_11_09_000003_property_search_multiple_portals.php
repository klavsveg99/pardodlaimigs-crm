<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A saved search may watch both portals at once, so the single `portal`
 * column becomes a JSON list (`portals`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('property_searches', function (Blueprint $table) {
            $table->renameColumn('portal', 'portals');
        });

        $this->convertValues(
            fn (string $value): string => json_encode([$value]) ?: $value,
        );
    }

    public function down(): void
    {
        $this->convertValues(
            fn (string $value): string => (string) (json_decode($value, true)[0] ?? $value),
        );

        Schema::table('property_searches', function (Blueprint $table) {
            $table->renameColumn('portals', 'portal');
        });
    }

    private function convertValues(callable $convert): void
    {
        foreach (DB::table('property_searches')->select('id', 'portals')->get() as $row) {
            $value = (string) $row->portals;

            DB::table('property_searches')
                ->where('id', $row->id)
                ->update(['portals' => $convert($value)]);
        }
    }
};
