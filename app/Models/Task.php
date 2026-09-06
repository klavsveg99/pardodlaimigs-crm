<?php

declare(strict_types=1);

namespace App\Models;

use App\Notifications\TaskAssignedNotification;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Log;

class Task extends Model
{
    protected $with = ['attachments'];

    protected $fillable = [
        'title', 'body', 'due_at', 'completed_at',
        'assigned_user_id', 'izpilditajs_id', 'created_by_user_id',
        'deal_id', 'client_id', 'property_id',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $t) {
            app(AuditLogger::class)->log('create', 'task', $t->id, null, $t->toArray());
            $t->dispatchAssignmentNotifications();
        });

        static::updated(function (self $t) {
            $changes = $t->getChanges();
            app(AuditLogger::class)->log('update', 'task', $t->id, array_intersect_key($t->getOriginal(), $changes), $changes);

            $assignmentChanged = array_key_exists('assigned_user_id', $changes) || array_key_exists('izpilditajs_id', $changes);
            $elapsed = $t->updated_at && $t->created_at
                ? $t->updated_at->getTimestamp() - $t->created_at->getTimestamp()
                : 0;

            if ($assignmentChanged && $elapsed > 5) {
                $t->dispatchAssignmentNotifications();
            }
        });

        static::deleted(fn ($t) => app(AuditLogger::class)->log('delete', 'task', $t->id, $t->toArray(), null));
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function izpilditajs(): BelongsTo
    {
        return $this->belongsTo(Izpilditajs::class, 'izpilditajs_id');
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

    public function dispatchAssignmentNotifications(): void
    {
        if ($this->assigned_user_id) {
            try {
                $assignee = $this->assignedTo;
                if ($assignee) {
                    $assignee->notify(new TaskAssignedNotification($this));
                }
            } catch (\Throwable $e) {
                Log::warning('TaskAssignedNotification to user failed', ['task' => $this->id, 'error' => $e->getMessage()]);
            }
        }

        if ($this->izpilditajs_id) {
            try {
                $sub = $this->izpilditajs;
                if ($sub && $sub->email) {
                    $sub->notify(new TaskAssignedNotification($this));
                }
            } catch (\Throwable $e) {
                Log::warning('TaskAssignedNotification to izpilditajs failed', ['task' => $this->id, 'error' => $e->getMessage()]);
            }
        }
    }
}