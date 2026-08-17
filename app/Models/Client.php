<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'email', 'personas_kods', 'source', 'gdpr_consent_at',
        'marketing_consent',
        'gdpr_erased_at', 'notes_md', 'owner_user_id',
    ];

    protected $with = ['attachments'];

    protected $casts = [
        'gdpr_consent_at' => 'datetime',
        'marketing_consent' => 'boolean',
        'gdpr_erased_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::forceDeleting(function (Client $client): void {
            $client->attachments()->get()->each(function ($attachment): void {
                Storage::disk($attachment->disk)->delete($attachment->path);
                $attachment->delete();
            });
        });
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
            ->withPivot('relation', 'notes_md')
            ->withTimestamps();
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
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
