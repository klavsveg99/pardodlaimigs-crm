<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class WpformEntry extends Model
{
    protected $table = 'wpform_entries';

    public $timestamps = false;

    protected $fillable = [
        'external_id', 'entry_id', 'form_id', 'form_name', 'status',
        'viewed', 'starred', 'ip_address', 'fields', 'client_id',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'entry_id' => 'integer',
        'form_id' => 'integer',
        'viewed' => 'boolean',
        'starred' => 'boolean',
        'fields' => 'array',
        'client_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Hard deletes are authoritative: persist a tombstone (so the
        // periodic WordPress sync never re-creates the entry) plus a full
        // archived snapshot (so the data survives even after the row is gone).
        static::deleted(function (WpformEntry $entry): void {
            static::tombstone($entry);
        });
    }

    /**
     * Records the entry as deleted so the periodic WordPress sync never
     * re-creates/resurrects it, and archives the payload locally.
     */
    public static function tombstone(WpformEntry $entry): void
    {
        if ($entry->external_id === null || $entry->external_id === '') {
            return;
        }

        DB::table('wpform_entry_deletions')->updateOrInsert(
            ['external_id' => (string) $entry->external_id],
            [
                'deleted_at' => now(),
                'entry_id' => $entry->entry_id,
                'form_id' => $entry->form_id,
                'form_name' => $entry->form_name,
                'client_id' => $entry->client_id,
                'fields' => $entry->fields !== null
                    ? json_encode($entry->fields, JSON_UNESCAPED_UNICODE)
                    : null,
                'entry_created_at' => $entry->created_at,
            ],
        );
    }

    public static function untombstone(string $externalId): void
    {
        DB::table('wpform_entry_deletions')->where('external_id', $externalId)->delete();
    }

    /**
     * CRM-side "delete": keep the row (so it stays viewable/restorable) but
     * move it to the "Dzēstie" tab and freeze it against WP re-sync.
     */
    public function moveToTrash(): void
    {
        static::tombstone($this);

        $this->update(['status' => 'deleted']);
    }

    public function restoreFromTrash(): void
    {
        static::untombstone((string) $this->external_id);

        $this->update(['status' => 'new']);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function fieldValue(string $name): ?string
    {
        foreach ($this->fields ?? [] as $field) {
            if (($field['name'] ?? '') !== $name) {
                continue;
            }
            $value = $field['value'] ?? null;
            if (is_string($value) || is_numeric($value)) {
                return (string) $value;
            }
            if (is_array($value)) {
                return implode(', ', array_filter(array_map('strval', $value)));
            }
        }

        return null;
    }
}
