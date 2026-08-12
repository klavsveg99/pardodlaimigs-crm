<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
