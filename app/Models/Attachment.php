<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'attachable_type', 'attachable_id', 'disk', 'path',
        'original_name', 'mime_type', 'size', 'sort_order',
    ];

    protected $casts = [
        'size' => 'integer',
        'sort_order' => 'integer',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        if (str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://')) {
            return $this->path;
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function cacheBustedUrl(): string
    {
        $url = $this->url;

        if (str_starts_with($this->path, 'http://') || str_starts_with($this->path, 'https://')) {
            return $url;
        }

        try {
            $version = (int) Storage::disk($this->disk)->lastModified($this->path);
        } catch (\Throwable $e) {
            $version = 0;
        }

        if ($version <= 0) {
            $version = (int) $this->size;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . 'v=' . $version;
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
