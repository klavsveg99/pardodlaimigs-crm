<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CrmProperty extends Model
{
    public const STATUSES = [
        'draft' => 'Melnraksts',
        'published' => 'Pārdošanā',
        'sold' => 'Pārdots',
        'deleted' => 'Dzēsts',
    ];

    public const CATEGORIES = [
        'Dzīvoklis' => 'Dzīvoklis',
        'Māja' => 'Māja',
        'Zeme' => 'Zeme',
        'Komerciāls' => 'Komerciāls',
        'Mežs' => 'Mežs',
        'Lauksaimniecības zeme' => 'Lauksaimniecības zeme',
    ];

    public const LEAD_SOURCES = [
        'internal' => 'Iekšējais (pardodlaimigs.lv)',
        'external' => 'Ārējais',
    ];

    protected $fillable = [
        'wp_post_id', 'title', 'slug', 'description', 'image_urls', 'price_cents', 'price_eur',
        'currency', 'category', 'status', 'lead_source', 'lead_owner', 'beds', 'baths',
        'size_m2', 'land_m2', 'kadastra_nr', 'city', 'address',
        'lat', 'lng', 'owner_user_id', 'sort_order',
        'final_price_eur', 'commission_eur', 'sold_at', 'ai_notes',
    ];

    protected $casts = [
        'price_cents' => 'integer',
        'price_eur' => 'decimal:2',
        'final_price_eur' => 'decimal:2',
        'commission_eur' => 'decimal:2',
        'sold_at' => 'datetime',
        'image_urls' => 'array',
        'beds' => 'integer',
        'baths' => 'integer',
        'size_m2' => 'integer',
        'land_m2' => 'integer',
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'sort_order' => 'integer',
        'ai_notes' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        // Admin URL izmanto slug: /properties/{slug}/edit
        return 'slug';
    }

    protected static function booted(): void
    {
        static::saving(function (self $property) {
            // Slug ir obligāts maršrutēšanai — jauniem ierakstiem ģenerējam
            // no nosaukuma. Esošus nekad nepārrakstām, lai WP saites nemainītos.
            if (empty($property->slug) && filled($property->title)) {
                $base = \Illuminate\Support\Str::slug((string) $property->title) ?: 'ipasums';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->whereKeyNot($property->getKey() ?? 0)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $property->slug = $slug;
            }
            if ($property->isDirty('status')) {
                if ($property->status === 'sold' && empty($property->sold_at)) {
                    $property->sold_at = now();
                }
            }
            // If final_price/commission set without sold status, keep sold_at; if needed, clear when not sold:
            // if ($property->status !== 'sold') { $property->sold_at = null; }
        });

        static::created(fn (self $p) => app(AuditLogger::class)->log('create', 'crm_property', $p->id, null, $p->toArray()));

        static::updated(function (self $p) {
            $changes = $p->getChanges();
            $meaningful = array_diff_key($changes, ['updated_at' => true]);
            if ($meaningful === []) {
                return;
            }
            app(AuditLogger::class)->log('update', 'crm_property', $p->id, array_intersect_key($p->getOriginal(), $changes), $changes);
        });

        static::deleted(fn (self $p) => app(AuditLogger::class)->log('delete', 'crm_property', $p->id, $p->toArray(), null));
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function clients(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'client_crm_properties', 'crm_property_id', 'client_id')
            ->using(ClientCrmProperty::class)
            ->withPivot('relation', 'notes_md')
            ->withTimestamps();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('sort_order');
    }

    public function descriptionRevisions(): HasMany
    {
        return $this->hasMany(CrmPropertyDescriptionRevision::class);
    }

    public function getPriceDisplayAttribute(): string
    {
        if ($this->price_eur <= 0) {
            return '';
        }

        return number_format((float) $this->price_eur, 0, '.', ' ').' €';
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getLeadSourceLabelAttribute(): string
    {
        return self::LEAD_SOURCES[$this->lead_source] ?? ($this->lead_source ?? '—');
    }

    public function getSelectionLabelAttribute(): string
    {
        $parts = [$this->title];
        if ($this->city) {
            $parts[] = $this->city;
        }
        if ($this->kadastra_nr) {
            $parts[] = $this->kadastra_nr;
        }

        return implode(' · ', $parts);
    }

    public function getPublicUrlAttribute(): string
    {
        $base = rtrim((string) config('wp-bridge.wordpress.site_url'), '/');

        // WP saite pēc post ID vienmēr aizved uz īsto ierakstu pat tad,
        // ja CRM slug novecojis vai nesakrīt ar WP post_name.
        if (filled($this->wp_post_id)) {
            return $base.'/?p='.(int) $this->wp_post_id;
        }

        return $base.'/ipasums/'.($this->slug ?: $this->id).'/';
    }

    public function getCommissionPercentAttribute(): ?float
    {
        $final = (float) ($this->final_price_eur ?? 0);
        $comm = (float) ($this->commission_eur ?? 0);

        if ($final <= 0 || $comm <= 0) {
            return null;
        }

        return round($comm / $final * 100, 2);
    }

    public function toWpPayload(): array
    {
        $agent = null;
        if ($this->owner) {
            $agent = [
                'id' => $this->owner->id,
                'name' => $this->owner->name,
                'email' => $this->owner->email,
            ];
        }

        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => (float) $this->price_eur,
            'currency' => $this->currency,
            'category' => $this->category,
            'status' => $this->status,
            'beds' => $this->beds,
            'baths' => $this->baths,
            'size_m2' => $this->size_m2,
            'land_m2' => $this->land_m2,
            'kadastra_nr' => $this->kadastra_nr,
            'city' => $this->city,
            'address' => $this->address,
            'lat' => $this->lat,
            'lng' => $this->lng,
            'crm_id' => $this->id,
            'sort_order' => $this->sort_order ?? $this->id,
            'agent' => $agent,
            'attachments' => $this->attachments->sortBy('sort_order')->values()->map(fn (Attachment $attachment) => [
                'url' => $attachment->url,
                'name' => $attachment->original_name,
                'mime_type' => $attachment->mime_type,
                'size' => (int) $attachment->size,
                'sort_order' => $attachment->sort_order,
            ])->concat(collect($this->image_urls ?? [])->values()->map(fn (string $url, int $index): array => [
                'url' => $url,
                'name' => basename(parse_url($url, PHP_URL_PATH) ?: "image-{$index}.jpg"),
                'mime_type' => 'image/*',
                'size' => 0,
                'sort_order' => $index,
            ]))->values()->all(),
        ];
    }
}
