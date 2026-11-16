<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Client "Avots" values that no longer exist in the form used to make the
 * edit page unsavable (the Select validation rejected the stored value).
 * Map known old labels to their current equivalent and keep anything else
 * under "Cits: ..." so no information is lost.
 */
return new class extends Migration
{
    private const ALLOWED = [
        'Tīmekļa vietne',
        'Sociālie tīkli',
        'Facebook',
        'Instagram',
        'Google',
        'Ieteikums',
        'Sludinājums',
        'Atgriešanās',
        'Cits',
    ];

    private const LEGACY = [
        'WP forma' => 'Tīmekļa vietne',
        'wp forma' => 'Tīmekļa vietne',
        'Mājaslapa' => 'Tīmekļa vietne',
        'mājaslapa' => 'Tīmekļa vietne',
    ];

    public function up(): void
    {
        $sources = DB::table('clients')
            ->whereNotNull('source')
            ->distinct()
            ->pluck('source');

        foreach ($sources as $source) {
            $source = trim((string) $source);

            if ($source === '') {
                DB::table('clients')->where('source', $source)->update(['source' => null]);

                continue;
            }

            if (in_array($source, self::ALLOWED, true) || str_starts_with($source, 'Cits:')) {
                continue;
            }

            $normalized = self::LEGACY[$source] ?? ('Cits: '.$source);

            DB::table('clients')
                ->where('source', $source)
                ->update(['source' => mb_substr($normalized, 0, 100)]);
        }
    }

    public function down(): void
    {
        // Data normalization is intentionally not reversible.
    }
};
