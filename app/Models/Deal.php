<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Deal extends Model
{
    public const STAGES = [
        'jauns' => 'Jauns',
        'pirma_tiksanas' => 'Pirmā tikšanās',
        'noslegta_sadarbiba' => 'Noslēgta sadarbība',
        'foto_video' => 'Foto/video',
        'tirgosana' => 'Tirgošana',
        'dokumentu_saskanosana' => 'Dokumentu saskaņošana',
        'pardots' => 'Pārdots',
    ];

    protected $fillable = [
        'title', 'client_id', 'property_id', 'stage',
        'value_eur', 'value_cents', 'currency', 'expected_close_date',
        'closed_at', 'owner_user_id',
    ];

    protected $with = ['attachments'];

    protected $casts = [
        'value_eur' => 'decimal:2',
        'value_cents' => 'integer',
        'expected_close_date' => 'date',
        'closed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Deal $d) {
            app(AuditLogger::class)->log('create', 'deal', $d->id, null, $d->toArray());
            app(AuditLogger::class)->activity('created', $d);
            app(AuditLogger::class)->activity('stage_changed', $d, [
                'from' => null,
                'to' => $d->stage,
                'initial' => true,
            ]);
        });

        static::updated(function (Deal $d) {
            $changes = $d->getChanges();
            if (isset($changes['stage'])) {
                app(AuditLogger::class)->activity('stage_changed', $d, [
                    'from' => $d->getOriginal('stage'),
                    'to' => $d->stage,
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

    public function stageChanges(): HasMany
    {
        return $this->hasMany(Activity::class)
            ->where('type', 'stage_changed')
            ->orderByDesc('created_at');
    }
}
