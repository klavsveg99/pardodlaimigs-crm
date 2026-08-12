<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyCache extends Model
{
    protected $table = 'properties_cache';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'id', 'title', 'slug', 'status', 'category', 'price_cents', 'currency',
        'beds', 'baths', 'size_m2', 'land_m2', 'lat', 'lng',
        'country', 'state', 'city', 'neighborhood', 'address',
        'type_ids', 'feature_ids', 'label_ids',
        'thumbnail_url', 'gallery_urls',
        'agent_wp_user_id', 'agency_wp_term_id',
        'wp_permalink', 'wp_updated_at', 'cached_at',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'beds' => 'integer',
        'baths' => 'integer',
        'size_m2' => 'decimal:2',
        'land_m2' => 'decimal:2',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'type_ids' => 'array',
        'feature_ids' => 'array',
        'label_ids' => 'array',
        'gallery_urls' => 'array',
        'wp_updated_at' => 'datetime',
        'cached_at' => 'datetime',
    ];

    public function getPriceDisplayAttribute(): ?string
    {
        if ($this->price_cents === null) {
            return null;
        }

        return number_format($this->price_cents / 100, 0, '.', ' ').' '.($this->currency ?? 'EUR');
    }

    public function getUrlAttribute(): ?string
    {
        return $this->wp_permalink;
    }
}
