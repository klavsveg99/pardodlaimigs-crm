<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropertySearch extends Model
{
    public const PORTALS = [
        'ss' => 'ss.lv',
        'city24' => 'city24.lv',
    ];

    public const CATEGORIES = [
        'land' => 'Zeme',
        'houses' => 'Mājas',
        'apartments' => 'Dzīvokļi',
        'commercial' => 'Komerciāls',
    ];

    /** Maximum saved searches an agent may create. Admins are unlimited. */
    public const AGENT_LIMIT = 3;

    protected $fillable = [
        'user_id', 'name', 'portals', 'category', 'location',
        'price_min', 'price_max', 'area_min', 'area_max', 'keywords',
        'max_results', 'is_active', 'last_run_at', 'last_error',
    ];

    protected $casts = [
        'portals' => 'array',
        'price_min' => 'integer',
        'price_max' => 'integer',
        'area_min' => 'integer',
        'area_max' => 'integer',
        'max_results' => 'integer',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function listings(): HasMany
    {
        return $this->hasMany(ScrapedListing::class);
    }

    public function newListings(): HasMany
    {
        return $this->listings()->where('status', 'new');
    }
}
