<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class KnowledgeArticle extends Model
{
    use SoftDeletes;

    public const TYPES = [
        'material' => 'Materiāls',
        'course' => 'Kurss',
    ];

    protected $fillable = [
        'knowledge_category_id', 'title', 'type', 'is_public', 'summary', 'body', 'video_urls', 'steps',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'video_urls' => 'array',
        'steps' => 'array',
    ];

    protected static function booted(): void
    {
        static::forceDeleted(function (self $article): void {
            $article->attachments()->get()->each(function (Attachment $attachment): void {
                Storage::disk($attachment->disk)->delete($attachment->path);
                $attachment->delete();
            });
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(KnowledgeCategory::class, 'knowledge_category_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(KnowledgeAssignment::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(KnowledgeCompletion::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('sort_order');
    }

    public function getVideoEmbedUrl(string $url): ?string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'youtu.be'], true)) {
            if ($host === 'youtu.be') {
                $id = trim($path, '/');
            } else {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
                $id = (string) ($query['v'] ?? '');
            }

            if (preg_match('/^[A-Za-z0-9_-]{11}$/', $id)) {
                return 'https://www.youtube-nocookie.com/embed/'.$id;
            }
        }

        if (in_array($host, ['vimeo.com', 'www.vimeo.com'], true) && preg_match('~^/(\d+)~', $path, $matches)) {
            return 'https://player.vimeo.com/video/'.$matches[1];
        }

        return null;
    }
}
