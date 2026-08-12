<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Task extends Model
{
    protected $with = ['attachments'];

    protected $fillable = [
        'title', 'body', 'due_at', 'completed_at',
        'assigned_user_id', 'created_by_user_id',
        'deal_id', 'client_id', 'property_id',
    ];

    protected $casts = [
        'due_at'       => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(fn ($t) => app(AuditLogger::class)->log('create', 'task', $t->id, null, $t->toArray()));
        static::updated(function ($t) {
            $changes = $t->getChanges();
            app(AuditLogger::class)->log('update', 'task', $t->id, array_intersect_key($t->getOriginal(), $changes), $changes);
        });
        static::deleted(fn ($t) => app(AuditLogger::class)->log('delete', 'task', $t->id, $t->toArray(), null));
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(PropertyCache::class, 'property_id');
    }

    public function isOverdue(): bool
    {
        return $this->completed_at === null
            && $this->due_at !== null
            && $this->due_at->isPast();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')->orderBy('sort_order');
    }
}
