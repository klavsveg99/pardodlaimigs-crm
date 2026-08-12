<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WpformEntry;
use App\Services\AuditLogger;
use App\Services\Wp\WpSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Pulls WPForms entries (wp-json/crm/v1/wpforms) into crm.wpform_entries.
 *
 * Entries are upserted by `external_id` ("form_id:entry_id"); entries
 * missing from a page are never deleted. The last-sync timestamp only
 * advances after the request and all processing complete successfully, so
 * a failed run re-fetches the same window on the next attempt.
 */
class SyncWpForms implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public const LAST_SYNC_KEY = 'wpforms_last_sync';

    public function handle(WpSource $source): void
    {
        $since = Cache::get(self::LAST_SYNC_KEY);
        $fetchedAt = now()->toIso8601String();

        $count = 0;
        foreach ($source->eachEntry($since ?: null) as $entry) {
            WpformEntry::updateOrCreate(
                ['external_id' => (string) $entry->external_id],
                $this->row($entry),
            );
            $count++;
        }

        // Persist the new sync cursor only once every page succeeded.
        Cache::forever(self::LAST_SYNC_KEY, $fetchedAt);

        app(AuditLogger::class)->log(
            'wpform_sync',
            'wpform_entry',
            null,
            null,
            ['count' => $count, 'since' => $since],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function row(object $entry): array
    {
        return [
            'entry_id' => (int) $entry->entry_id,
            'form_id' => (int) $entry->form_id,
            'form_name' => $entry->form_name ?: null,
            'status' => $entry->status ?: null,
            'viewed' => (bool) ($entry->viewed ?? false),
            'starred' => (bool) ($entry->starred ?? false),
            'ip_address' => $entry->ip_address ?: null,
            'fields' => $this->fields((array) ($entry->fields ?? [])),
            'created_at' => $this->timestamp($entry->created_at),
            'updated_at' => $this->timestamp($entry->updated_at),
        ];
    }

    /**
     * @param  array<int, mixed>  $fields
     * @return array<int, array{id: int, name: string, type: string, value: mixed}>
     */
    private function fields(array $fields): array
    {
        return array_map(static function (mixed $field): array {
            $field = (array) $field;

            return [
                'id' => (int) ($field['id'] ?? 0),
                'name' => (string) ($field['name'] ?? ''),
                'type' => (string) ($field['type'] ?? ''),
                'value' => $field['value'] ?? null,
            ];
        }, $fields);
    }

    private function timestamp(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
