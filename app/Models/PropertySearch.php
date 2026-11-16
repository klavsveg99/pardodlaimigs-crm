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

    /** Queue/run progress shown in the Meklētāji table and edit page. */
    public const PROGRESS_IDLE = 'idle';

    public const PROGRESS_QUEUED = 'queued';

    public const PROGRESS_RUNNING = 'running';

    public const PROGRESS_COMPLETED = 'completed';

    public const PROGRESS_FAILED = 'failed';

    protected $fillable = [
        'user_id', 'name', 'portals', 'category', 'location',
        'price_min', 'price_max', 'area_min', 'area_max', 'keywords',
        'is_active', 'frequency', 'last_run_at', 'last_error',
        'progress_status', 'progress_current', 'progress_total',
        'progress_found', 'progress_message',
        'progress_started_at', 'progress_finished_at',
    ];

    protected $casts = [
        'portals' => 'array',
        'price_min' => 'integer',
        'price_max' => 'integer',
        'area_min' => 'integer',
        'area_max' => 'integer',
        'is_active' => 'boolean',
        'last_run_at' => 'datetime',
        'progress_current' => 'integer',
        'progress_total' => 'integer',
        'progress_found' => 'integer',
        'progress_started_at' => 'datetime',
        'progress_finished_at' => 'datetime',
    ];

    public function isProgressActive(): bool
    {
        return in_array($this->progress_status, [
            self::PROGRESS_QUEUED,
            self::PROGRESS_RUNNING,
        ], true);
    }

    public function markProgressQueued(int $total): void
    {
        $this->forceFill([
            'progress_status' => self::PROGRESS_QUEUED,
            'progress_current' => 0,
            'progress_total' => max(0, $total),
            'progress_found' => 0,
            'progress_message' => 'Gaida rindā...',
            'progress_started_at' => null,
            'progress_finished_at' => null,
        ])->save();
    }

    public function markProgressRunning(int $total): void
    {
        $this->forceFill([
            'progress_status' => self::PROGRESS_RUNNING,
            'progress_current' => 0,
            'progress_total' => max(0, $total),
            'progress_found' => 0,
            'progress_message' => 'Sāk meklēšanu...',
            'progress_started_at' => now(),
            'progress_finished_at' => null,
        ])->save();
    }

    public function updateProgress(int $current, int $total, string $message, int $found): void
    {
        $this->forceFill([
            'progress_status' => self::PROGRESS_RUNNING,
            'progress_current' => max(0, $current),
            'progress_total' => max(0, $total),
            'progress_found' => max(0, $found),
            'progress_message' => mb_substr($message, 0, 255),
        ])->save();
    }

    public function markProgressCompleted(int $found, ?string $message = null): void
    {
        $this->forceFill([
            'progress_status' => self::PROGRESS_COMPLETED,
            'progress_current' => $this->progress_total,
            'progress_found' => max(0, $found),
            'progress_message' => $message ?: 'Pabeigts. Atrasti '.max(0, $found).' jauni objekti.',
            'progress_finished_at' => now(),
        ])->save();
    }

    public function markProgressFailed(string $message): void
    {
        $this->forceFill([
            'progress_status' => self::PROGRESS_FAILED,
            'progress_message' => mb_substr($message, 0, 255),
            'progress_finished_at' => now(),
        ])->save();
    }

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
