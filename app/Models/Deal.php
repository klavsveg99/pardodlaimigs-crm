<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class Deal extends Model
{
    public const STAGES = [
        'lead'             => 'Sākotnējais interesents',
        'viewing_scheduled'=> 'Apskate ieplānota',
        'offer'            => 'Piedāvājums',
        'reserved'         => 'Rezervēts',
        'closed_won'       => 'Pārdots',
        'closed_lost'      => 'Zaudēts',
    ];

    protected $fillable = [
        'client_id', 'property_id', 'stage',
        'value_cents', 'currency', 'expected_close_date',
        'closed_at', 'owner_user_id',
    ];

    protected $with = ['attachments'];

    protected $casts = [
        'value_cents'         => 'integer',
        'expected_close_date' => 'date',
        'closed_at'           => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Deal $d) {
            app(AuditLogger::class)->log('create', 'deal', $d->id, null, $d->toArray());
            app(AuditLogger::class)->activity('created', $d);
        });

        static::updated(function (Deal $d) {
            $changes = $d->getChanges();
            if (isset($changes['stage'])) {
                app(AuditLogger::class)->activity('stage_changed', $d, [
                    'from' => $d->getOriginal('stage'),
                    'to'   => $d->stage,
                ]);
            }
            app(AuditLogger::class)->log(
                'update', 'deal', $d->id,
                array_intersect_key($d->getOriginal(), $changes),
                $changes
            );
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(PropertyCache::class, 'property_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function getStageLabelAttribute(): string
    {
        return self::STAGES[$this->stage] ?? $this->stage;
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('sort_order');
    }
}
