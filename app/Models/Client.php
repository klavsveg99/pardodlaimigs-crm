<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'email', 'source', 'gdpr_consent_at',
        'gdpr_erased_at', 'notes_md', 'owner_user_id',
    ];

    protected $with = ['attachments'];

    protected $casts = [
        'gdpr_consent_at' => 'datetime',
        'gdpr_erased_at'  => 'datetime',
    ];

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
