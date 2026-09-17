<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Deleted WPForms entries used to be hard-deleted, keeping only a tombstone
 * (external_id + archived payload). The CRM now keeps deleted entries in
 * wpform_entries with status "deleted" so they can be viewed/restored in the
 * "Dzēstie" tab. This migration recreates those rows from the tombstones.
 */
return new class extends Migration
{
    public function up(): void
    {
        $tombstones = DB::table('wpform_entry_deletions')->get();

        foreach ($tombstones as $tombstone) {
            $externalId = (string) $tombstone->external_id;
            if ($externalId === '') {
                continue;
            }

            $alreadyExists = DB::table('wpform_entries')
                ->where('external_id', $externalId)
                ->exists();
            if ($alreadyExists) {
                continue;
            }

            $entryId = $tombstone->entry_id;
            $formId = $tombstone->form_id;
            if ($entryId === null || $formId === null) {
                [$formId, $entryId] = array_pad(explode(':', $externalId, 2), 2, null);
            }
            if (! is_numeric($entryId) || ! is_numeric($formId)) {
                continue;
            }

            $clientId = $tombstone->client_id;
            if ($clientId !== null && ! DB::table('clients')->where('id', $clientId)->exists()) {
                $clientId = null;
            }

            DB::table('wpform_entries')->insert([
                'external_id' => $externalId,
                'entry_id' => (int) $entryId,
                'form_id' => (int) $formId,
                'form_name' => $tombstone->form_name,
                'status' => 'deleted',
                'viewed' => false,
                'starred' => false,
                'ip_address' => null,
                'fields' => $tombstone->fields,
                'client_id' => $clientId,
                'created_at' => $tombstone->entry_created_at,
                'updated_at' => $tombstone->entry_created_at,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('wpform_entries')->where('status', 'deleted')->delete();
    }
};
