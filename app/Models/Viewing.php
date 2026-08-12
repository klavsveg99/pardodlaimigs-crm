<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Viewing extends Model
{
    protected $with = ['attachments'];

    protected $fillable = [
        'property_id', 'client_id', 'agent_user_id',
        'scheduled_at', 'duration_min', 'status', 'notes_md',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (Viewing $v) {
            app(AuditLogger::class)->log('create', 'viewing', $v->id, null, $v->toArray());
            app(AuditLogger::class)->activity('viewing_booked', null, [
                'viewing_id' => $v->id,
                'property_id' => $v->property_id,
                'client_id' => $v->client_id,
                'scheduled_at' => $v->scheduled_at?->toIso8601String(),
            ]);
        });
        static::updated(function (Viewing $v) {
            $changes = $v->getChanges();
            app(AuditLogger::class)->log('update', 'viewing', $v->id,
                array_intersect_key($v->getOriginal(), $changes), $changes);
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(PropertyCache::class, 'property_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('sort_order');
    }
}
