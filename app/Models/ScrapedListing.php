<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapedListing extends Model
{
    public const STATUSES = [
        'new' => 'Jauns',
        'seen' => 'Apskatīts',
        'dismissed' => 'Atteikts',
    ];

    protected $fillable = [
        'property_search_id', 'user_id', 'portal', 'external_id', 'url',
        'title', 'price_eur', 'area_m2', 'land_m2', 'location', 'address',
        'image_url', 'listed_at', 'first_seen_at', 'last_seen_at', 'status',
        'duplicate_of_id', 'fingerprint',
    ];

    protected $casts = [
        'price_eur' => 'decimal:2',
        'area_m2' => 'integer',
        'land_m2' => 'integer',
        'listed_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function search(): BelongsTo
    {
        return $this->belongsTo(PropertySearch::class, 'property_search_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'duplicate_of_id');
    }
}
