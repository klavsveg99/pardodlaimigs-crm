<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

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
            app(AuditLogger::class)->activity('viewing_booked', [
                'viewing_id' => $v->id,
                'property_id' => $v->property_id,
                'client_id' => $v->client_id,
                'scheduled_at' => $v->scheduled_at?->toIso8601String(),
            ]);
        });
        static::updated(function (Viewing $v) {
            $changes = $v->getChanges();
            $meaningful = array_diff_key($changes, ['updated_at' => true]);
            if ($meaningful === []) {
                return;
            }
            app(AuditLogger::class)->log('update', 'viewing', $v->id,
                array_intersect_key($v->getOriginal(), $changes), $changes);
        });

        static::deleted(function (self $viewing) {
            // Pielikumu atkritumi — dzēšam arī failus (attachable = viewing).
            $viewing->attachments()->get()->each(function ($attachment) {
                \Illuminate\Support\Facades\Storage::disk($attachment->disk)->delete($attachment->path);
                $attachment->delete();
            });
        });
    }

    /**
     * Vai apskatei norādīts konkrēts laiks. Ja laiks nav norādīts (pusnakts),
     * to uzskatām par "nav laika" — kalendārā rādām kā visas dienas notikumu.
     */
    public function scheduledHasTime(): bool
    {
        return $this->scheduled_at !== null && $this->scheduled_at->format('H:i') !== '00:00';
    }

    /** Apskates laika attēlojums: bez laika, ja tas nav norādīts. */
    public function getScheduledDisplayAttribute(): ?string
    {
        if ($this->scheduled_at === null) {
            return null;
        }

        return $this->scheduledHasTime()
            ? $this->scheduled_at->locale('lv')->translatedFormat('d.m.Y H:i')
            : $this->scheduled_at->locale('lv')->translatedFormat('d.m.Y');
    }

    /**
     * Apskates brīdis salīdzinājumiem. Ja laiks nav norādīts, uzskatām to par
     * visas dienas notikumu — nokavēta tikai pēc dienas beigām, nevis jau
     * pusnaktī. Ar konkrētu laiku — nokavēta, kad laiks pagājis.
     */
    public function effectiveScheduledAt(): ?Carbon
    {
        if ($this->scheduled_at === null) {
            return null;
        }

        return $this->scheduledHasTime()
            ? $this->scheduled_at
            : $this->scheduled_at->copy()->endOfDay();
    }

    /** Vai apskate ir nokavēta (ieplānota, bet tās laiks/diena pagājusi). */
    public function isOverdue(): bool
    {
        $at = $this->effectiveScheduledAt();

        return $this->status === 'scheduled'
            && $at !== null
            && $at->isPast();
    }

    public function property(): BelongsTo
    {
        // Apskates mērķē tikai uz CRM īpašumiem.
        return $this->belongsTo(CrmProperty::class, 'property_id');
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
