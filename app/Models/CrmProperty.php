<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CrmProperty extends Model
{
    public const STATUSES = [
        'draft' => 'Melnraksts',
        'published' => 'Publicēts',
        'expired' => 'Beidzies',
        'hidden' => 'Slēpts',
        'sold' => 'Pārdots',
    ];

    public const CATEGORIES = [
        'Dzīvoklis' => 'Dzīvoklis',
        'Māja' => 'Māja',
        'Zeme' => 'Zeme',
        'Komerciāls' => 'Komerciāls',
        'Pirts' => 'Pirts',
        'Garāža' => 'Garāža',
    ];

    protected $fillable = [
        'wp_post_id', 'title', 'slug', 'description', 'image_urls', 'price_cents', 'price_eur',
        'currency', 'category', 'status', 'beds', 'baths',
        'size_m2', 'land_m2', 'kadastra_nr', 'city', 'address',
        'lat', 'lng', 'owner_user_id',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'price_eur' => 'decimal:2',
        'image_urls' => 'array',
        'beds' => 'integer',
        'baths' => 'integer',
        'size_m2' => 'integer',
        'land_m2' => 'integer',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_crm_properties', 'crm_property_id', 'client_id')
            ->withPivot('relation', 'notes_md')
            ->withTimestamps();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('sort_order');
    }

    public function getPriceDisplayAttribute(): string
    {
        if ($this->price_eur <= 0) {
            return '';
        }

        return number_format((float) $this->price_eur, 0, '.', ' ').' €';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getSelectionLabelAttribute(): string
    {
        $parts = [$this->title];
        if ($this->city) {
            $parts[] = $this->city;
        }
        if ($this->kadastra_nr) {
            $parts[] = $this->kadastra_nr;
        }

        return implode(' · ', $parts);
    }

    public function getPublicUrlAttribute(): string
    {
        return rtrim((string) config('wp-bridge.wordpress.site_url'), '/')
            .'/ipasums/'.($this->slug ?: $this->wp_post_id ?: $this->id).'/';
    }

    public function toWpPayload(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price_eur,
            'currency' => $this->currency,
            'category' => $this->category,
            'status' => $this->status,
            'beds' => $this->beds,
            'baths' => $this->baths,
            'size_m2' => $this->size_m2,
            'land_m2' => $this->land_m2,
            'kadastra_nr' => $this->kadastra_nr,
            'city' => $this->city,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'crm_id' => $this->id,
            'attachments' => $this->attachments->map(fn (Attachment $attachment) => [
                'url' => $attachment->url,
                'name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'sort_order' => $attachment->sort_order,
            ])->concat(collect($this->image_urls ?? [])->values()->map(fn (string $url, int $index): array => [
                'url' => $url,
                'name' => basename(parse_url($url, PHP_URL_PATH) ?: "image-{$index}.jpg"),
                'mime_type' => 'image/*',
                'sort_order' => $index,
            ]))->values()->all(),
        ];
    }
}
