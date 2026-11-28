<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Property enquiries need a real property link so agents can be scoped to
     * their own submissions and notified about them.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('wpform_entries', 'property_id')) {
            Schema::table('wpform_entries', function (Blueprint $table): void {
                // SQLite cannot add a foreign key to an existing table; the
                // index is enough for the ownership lookups.
                $table->unsignedBigInteger('property_id')->nullable()->index()->after('client_id');
            });
        }

        $this->normalizeActiveFormNames();
        $this->backfillPropertyIds();
    }

    public function down(): void
    {
        if (Schema::hasColumn('wpform_entries', 'property_id')) {
            Schema::table('wpform_entries', function (Blueprint $table): void {
                $table->dropColumn('property_id');
            });
        }
    }

    /**
     * Only active entries are renamed: the "Dzēstie" tab keeps the historical
     * WordPress form name it was imported with.
     */
    private function normalizeActiveFormNames(): void
    {
        $active = fn ($query) => $query->where(
            fn ($q) => $q->whereNull('status')->orWhere('status', '!=', 'deleted'),
        );

        $active(DB::table('wpform_entries'))
            ->whereIn('form_name', ['dzīvoklis', 'Vietnes īpašuma vērtēšanas pieteikums', 'Kontakta forma'])
            ->update(['form_name' => 'Galvenā kontaktforma']);

        $active(DB::table('wpform_entries'))
            ->where('form_name', 'Pieprasījums par īpašumu')
            ->update(['form_name' => 'Īpašuma kontaktforma']);
    }

    /**
     * Historic property enquiries only carry the listing URL in their
     * "Īpašuma saite" field. Match its slug to the CRM property.
     */
    private function backfillPropertyIds(): void
    {
        $slugs = DB::table('crm_properties')
            ->whereNotNull('slug')
            ->pluck('id', 'slug');

        if ($slugs->isEmpty()) {
            return;
        }

        DB::table('wpform_entries')
            ->whereNull('property_id')
            ->whereNotNull('fields')
            ->orderBy('id')
            ->chunkById(200, function ($entries) use ($slugs): void {
                foreach ($entries as $entry) {
                    $fields = json_decode((string) $entry->fields, true);
                    if (! is_array($fields)) {
                        continue;
                    }

                    $url = null;
                    foreach ($fields as $field) {
                        if (($field['name'] ?? '') === 'Īpašuma saite') {
                            $url = (string) ($field['value'] ?? '');
                            break;
                        }
                    }

                    if (! is_string($url) || $url === '') {
                        continue;
                    }

                    $path = parse_url($url, PHP_URL_PATH);
                    if (! is_string($path)) {
                        continue;
                    }

                    $slug = trim((string) basename(rtrim($path, '/')));
                    $propertyId = $slugs->get($slug);
                    if ($propertyId === null) {
                        continue;
                    }

                    DB::table('wpform_entries')
                        ->where('id', $entry->id)
                        ->update(['property_id' => $propertyId]);
                }
            });
    }
};
