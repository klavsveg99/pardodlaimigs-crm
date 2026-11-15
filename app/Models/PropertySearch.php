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

    /** How often an active search re-checks the portals on its own. */
    public const FREQUENCIES = [
        'manual' => 'Tikai manuāli',
        'six_hours' => 'Ik pēc 6 stundām',
        'daily' => 'Reizi dienā',
        'weekly' => 'Reizi nedēļā',
    ];

    private const FREQUENCY_MINUTES = [
        'six_hours' => 360,
        'daily' => 1440,
        'weekly' => 10080,
    ];

    /** Maximum saved searches an agent may create. Admins are unlimited. */
    public const AGENT_LIMIT = 3;

    protected $fillable = [
        'user_id', 'name', 'portals', 'category', 'location',
        'price_min', 'price_max', 'area_min', 'area_max', 'keywords',
        'max_results', 'is_active', 'frequency', 'last_run_at', 'last_error',
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

    /** Minutes between automatic runs, null when the search only runs manually. */
    public function intervalMinutes(): ?int
    {
        return self::FREQUENCY_MINUTES[$this->frequency] ?? null;
    }

    /** True when the search runs automatically and its interval has elapsed. */
    public function isDue(): bool
    {
        $minutes = $this->intervalMinutes();

        if ($minutes === null) {
            return false;
        }

        return $this->last_run_at === null
            || $this->last_run_at->copy()->addMinutes($minutes)->isPast();
    }

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
