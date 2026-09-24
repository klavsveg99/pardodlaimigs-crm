<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

/**
 * Follow Up līdis: potenciāls pārdevējs, kurš vēl nav gatavs uzsākt
 * sadarbību (mantojuma lieta, šķiršanās, dokumenti u.tml.). Tā ir
 * pirms-sadarbības stadija, nevis pazaudēts klients.
 */
class FollowUpLead extends Model
{
    use SoftDeletes;

    public const STATUSES = [
        'active' => 'Aktīvs',
        'closed' => 'Sadarbība uzsākta',
        'dropped' => 'Pārtraukts',
    ];

    public const REASONS = [
        'Mantojuma lieta' => 'Mantojuma lieta',
        'Šķiršanās' => 'Šķiršanās',
        'Dokumentu kārtošana' => 'Dokumentu kārtošana',
        'Remonts / sagatavošana' => 'Remonts / sagatavošana',
        'Cena' => 'Cena',
        'Īrnieks / termiņš' => 'Īrnieks / termiņš',
        'Kredīts' => 'Kredīts',
        'Cits' => 'Cits',
    ];

    /** Atgādinājuma intervāli (dienas). */
    public const CADENCES = [
        7 => 'Katru nedēļu',
        14 => 'Ik pēc 2 nedēļām',
        30 => 'Reizi mēnesī',
    ];

    protected $fillable = [
        'client_id', 'crm_property_id', 'property_address',
        'conversation_started_at', 'reason', 'reason_note',
        'target_start_at', 'next_contact_at', 'cadence_days',
        'last_contacted_at', 'contact_count', 'status',
        'owner_user_id', 'notes', 'source_wpform_entry_id',
    ];

    protected $casts = [
        'conversation_started_at' => 'date',
        'target_start_at' => 'date',
        'next_contact_at' => 'date',
        'last_contacted_at' => 'datetime',
        'cadence_days' => 'integer',
        'contact_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::created(fn (self $lead) => app(AuditLogger::class)->log('create', 'follow_up_lead', $lead->id, null, $lead->toArray()));

        static::updated(function (self $lead): void {
            $changes = $lead->getChanges();
            $meaningful = array_diff_key($changes, ['updated_at' => true]);
            if ($meaningful === []) {
                return;
            }
            app(AuditLogger::class)->log('update', 'follow_up_lead', $lead->id, array_intersect_key($lead->getOriginal(), $changes), $changes);
        });

        static::deleted(fn (self $lead) => app(AuditLogger::class)->log('delete', 'follow_up_lead', $lead->id, $lead->toArray(), null));
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(CrmProperty::class, 'crm_property_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function sourceEntry(): BelongsTo
    {
        return $this->belongsTo(WpformEntry::class, 'source_wpform_entry_id');
    }

    /** Atgādinājuma taupīšanas (throttle) atslēga; viena uz līdi. */
    public static function reminderCacheKey(int $id): string
    {
        return "followup-reminder:{$id}";
    }

    /** Aktīvs un nākamais kontakts jau pienācis / nokavēts. */
    public function isOverdue(): bool
    {
        return $this->status === 'active'
            && $this->next_contact_at !== null
            && $this->next_contact_at->lt(today());
    }

    /** Aktīvi līdi, kam šodien vai agrāk jāsazinās. */
    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->whereNotNull('next_contact_at')
            ->whereDate('next_contact_at', '<=', today());
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Aģents sazinājās: pieraksta kontaktu un ieplāno nākamo atgādinājumu
     * pēc izvēlētā intervāla. Atgādinājuma throttle tiek notīrīts, lai
     * nākamais cikls sāktos no jauna.
     */
    public function markContacted(?string $note = null): void
    {
        $cadence = max(1, (int) ($this->cadence_days ?: 7));

        if (filled($note)) {
            $stamp = now()->format('d.m.Y H:i').' — '.trim($note);
            $this->notes = trim(($this->notes ? $this->notes."\n" : '').$stamp);
        }

        $this->forceFill([
            'last_contacted_at' => now(),
            'contact_count' => (int) $this->contact_count + 1,
            'next_contact_at' => today()->addDays($cadence),
        ])->save();

        Cache::forget(self::reminderCacheKey((int) $this->getKey()));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getTitleAttribute(): string
    {
        $parts = array_filter([
            $this->client?->name,
            $this->property?->title ?? $this->property_address,
        ]);

        return $parts ? implode(' · ', $parts) : ('Līdis #'.$this->getKey());
    }
}
