<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Support\PhoneFormat;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Client extends Model
{
    use HasSlug;
    use SoftDeletes;

    protected $fillable = [
        'name', 'person_type', 'phone', 'email', 'personas_kods', 'birth_date', 'birthday_greeted_at',
        'address', 'bank_account', 'source', 'status', 'gdpr_consent_at',
        'marketing_consent',
        'gdpr_erased_at', 'notes_md', 'owner_user_id',
    ];

    /** Personas veids darījumā. */
    public const PERSON_TYPES = [
        'fiziska' => 'Fiziska persona',
        'juridiska' => 'Juridiskā persona',
    ];

    /** Klienta statuss: parasts klients, līds vai noslēgta darījuma klients. */
    public const STATUSES = [
        'active' => 'Aktīvs',
        'lead' => 'Līds',
        'laimigs' => 'Laimīgs',
    ];

    public function scopeLeads(Builder $query): Builder
    {
        return $query->where('status', 'lead');
    }

    public function isLead(): bool
    {
        return $this->status === 'lead';
    }

    public function isLaimigs(): bool
    {
        return $this->status === 'laimigs';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ($this->status ?? '—');
    }

    protected $with = ['attachments'];

    protected $casts = [
        'gdpr_consent_at' => 'datetime',
        'marketing_consent' => 'boolean',
        'gdpr_erased_at' => 'datetime',
        'birth_date' => 'date',
        'birthday_greeted_at' => 'date',
    ];

    /** Vai klients darījumā ir juridiska persona (SIA u.c.). */
    public function isLegalPerson(): bool
    {
        return $this->person_type === 'juridiska';
    }

    // Vārds tiek normalizēts: tikai pirmie burti lielie
    // ("VINETA IVANOVA" → "Vineta Ivanova"). Juridisko personu nosaukumus
    // (piem. "SIA ABC") nemainām, jo MB_CASE_TITLE tos sabojā ("Sia Abc").
    protected function name(): Attribute
    {
        return Attribute::set(function ($value): ?string {
            if (! filled($value)) {
                return $value;
            }

            $value = trim((string) $value);

            // Juridiskā persona vai nosaukums ar uzņēmuma formas saīsinājumu
            // (SIA, AS, IK u.c.) paliek lietotāja ievadītajā formā.
            if (($this->attributes['person_type'] ?? null) === 'juridiska'
                || preg_match('/^(sia|as|ik|iu|zs|ps|vas)\b/iu', $value)) {
                return $value;
            }

            return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
        });
    }

    /**
     * Latvijas personas kods DDMMYY-XXXXX; 7. cipars norāda gadsimtu
     * (0 → 18xx, 1 → 19xx, 2 → 20xx). Atgriež null, ja kods nav derīgs.
     */
    public static function birthDateFromPersonasKods(?string $code): ?Carbon
    {
        $digits = preg_replace('/\D/', '', (string) $code);

        if (strlen($digits) !== 11) {
            return null;
        }

        $day = (int) substr($digits, 0, 2);
        $month = (int) substr($digits, 2, 2);
        $yearShort = (int) substr($digits, 4, 2);
        $century = match ((int) $digits[6]) {
            0 => 1800,
            1 => 1900,
            2 => 2000,
            default => null,
        };

        if ($century === null) {
            return null;
        }

        $year = $century + $yearShort;

        return checkdate($month, $day, $year) ? Carbon::create($year, $month, $day)->startOfDay() : null;
    }

    /**
     * Šī gada dzimšanas diena (datums), vai null, ja nav zināms datums.
     * 29. februāris neizlēciena gadā pārceļas uz 1. martu (Carbon uzvedība).
     */
    public function birthdayThisYear(): ?Carbon
    {
        if (! $this->birth_date) {
            return null;
        }

        return Carbon::create(now()->year, $this->birth_date->month, $this->birth_date->day)->startOfDay();
    }

    protected static function booted(): void
    {
        // Dzimšanas datumu iegūstam no personas koda (DDMMYY-XXXXX), ja tas
        // vēl nav norādīts. Jau esošu datumu nepārrakstām.
        static::saving(function (Client $client): void {
            if (blank($client->birth_date) && filled($client->personas_kods)) {
                $client->birth_date = self::birthDateFromPersonasKods($client->personas_kods);
            }
        });

        // "Laimīgos" klientus (noslēgts darījums) dzēst nedrīkst — ne ar
        // mīksto, ne neatgriezenisko dzēšanu, ne adminiem, ne aģentiem.
        static::deleting(fn (Client $client): bool => ! $client->isLaimigs());
        static::forceDeleting(fn (Client $client): bool => ! $client->isLaimigs());

        static::forceDeleting(function (Client $client): void {
            $client->attachments()->get()->each(function ($attachment): void {
                Storage::disk($attachment->disk)->delete($attachment->path);
                $attachment->delete();
            });
        });

        static::created(fn (Client $c) => app(AuditLogger::class)->log('create', 'client', $c->id, null, $c->toArray()));

        static::updated(function (Client $c) {
            $changes = $c->getChanges();
            $meaningful = array_diff_key($changes, ['updated_at' => true]);
            if ($meaningful === []) {
                return;
            }
            app(AuditLogger::class)->log('update', 'client', $c->id, array_intersect_key($c->getOriginal(), $changes), $changes);
        });

        static::deleted(fn (Client $c) => app(AuditLogger::class)->log('delete', 'client', $c->id, $c->toArray(), null));
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(PropertyCache::class, 'client_properties', 'client_id', 'property_id')
            ->withPivot('relation', 'notes_md')
            ->withTimestamps();
    }

    public function crmProperties(): BelongsToMany
    {
        return $this->belongsToMany(CrmProperty::class, 'client_crm_properties', 'client_id', 'crm_property_id')
            ->using(ClientCrmProperty::class)
            ->withPivot('relation', 'notes_md')
            ->withTimestamps();
    }

    public function viewings(): HasMany
    {
        return $this->hasMany(Viewing::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('sort_order');
    }

    public function wpformEntries(): HasMany
    {
        return $this->hasMany(WpformEntry::class)->orderByDesc('created_at');
    }
}
