<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\AuditLogger;
use App\Services\Mail\EmailSender;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Task extends Model
{
    protected $with = ['attachments'];

    protected $fillable = [
        'title', 'body', 'due_at', 'completed_at',
        'agent_notified_at', 'izpilditajs_notified_at',
        'assigned_user_id', 'izpilditajs_id', 'created_by_user_id',
        'client_id', 'property_id',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'agent_notified_at' => 'datetime',
        'izpilditajs_notified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $t) {
            app(AuditLogger::class)->log('create', 'task', $t->id, null, $t->toArray());
        });

        static::updated(function (self $t) {
            $changes = $t->getChanges();
            $meaningful = array_diff_key($changes, ['updated_at' => true]);
            if ($meaningful !== []) {
                app(AuditLogger::class)->log('update', 'task', $t->id, array_intersect_key($t->getOriginal(), $changes), $changes);
            }
        });

        static::deleted(function (self $t) {
            app(AuditLogger::class)->log('delete', 'task', $t->id, $t->toArray(), null);

            // Pielikumi bez saimnieka ir atkritumi — dzēšam arī failus.
            $t->attachments()->get()->each(function ($attachment) {
                Storage::disk($attachment->disk)->delete($attachment->path);
                $attachment->delete();
            });
        });
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function izpilditajs(): BelongsTo
    {
        return $this->belongsTo(Izpilditajs::class, 'izpilditajs_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(CrmProperty::class, 'property_id');
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

    /**
     * Send the assignment email to the task's agent. Returns null on success,
     * otherwise a user-facing error message. Never sent automatically — the
     * "Nosūtīt paziņojumu" section on the task page triggers it.
     */
    public function sendAssignmentNotificationToAgent(): ?string
    {
        $user = $this->assignedTo;
        if (! $user) {
            return 'Uzdevumam nav piešķirts aģents.';
        }
        if (blank($user->email)) {
            return 'Aģentam nav norādīts e-pasts.';
        }

        $error = $this->sendAssignmentEmail($user->email, 'Aģents: '.$user->name);
        if ($error === null) {
            $this->forceFill(['agent_notified_at' => now()])->saveQuietly();
        }

        return $error;
    }

    /**
     * Send the assignment email to the task's izpildītājs. Returns null on
     * success, otherwise a user-facing error message.
     */
    public function sendAssignmentNotificationToIzpilditajs(): ?string
    {
        $sub = $this->izpilditajs;
        if (! $sub) {
            return 'Uzdevumam nav piesaistīts izpildītājs.';
        }
        if (blank($sub->email)) {
            return 'Izpildītājam nav norādīts e-pasts.';
        }

        $error = $this->sendAssignmentEmail($sub->email, 'Izpildītājs: '.$sub->name);
        if ($error === null) {
            $this->forceFill(['izpilditajs_notified_at' => now()])->saveQuietly();
        }

        return $error;
    }

    /**
     * Shared assignment email body. Uses the project-wide EmailSender so the
     * template matches every other outgoing email. Attachments of the task are
     * included.
     */
    private function sendAssignmentEmail(string $to, string $recipientLabel): ?string
    {
        $due = $this->due_at?->locale('lv')->translatedFormat('d.m.Y H:i') ?? '—';

        $rows = ['<p>Jums piešķirts jauns uzdevums: <strong>'.$this->title.'</strong></p>'];

        if (filled($this->body)) {
            $rows[] = '<p>'.nl2br(e((string) $this->body)).'</p>';
        }

        $rows[] = '<p><strong>Termiņš:</strong> '.e($due).'</p>';
        $rows[] = '<p><strong>'.$recipientLabel.'</strong></p>';
        $rows[] = $this->assignedTo ? '<p><strong>Aģents:</strong> '.e($this->assignedTo->name).'</p>' : '';
        $rows[] = $this->izpilditajs ? '<p><strong>Izpildītājs:</strong> '.e($this->izpilditajs->name).'</p>' : '';
        $rows[] = $this->client ? '<p><strong>Klients:</strong> '.e($this->client->name).'</p>' : '';
        $rows[] = $this->property ? '<p><strong>Īpašums:</strong> '.e($this->property->title).'</p>' : '';

        $body = implode('', array_filter($rows));

        try {
            app(EmailSender::class)->send(
                to: $to,
                subject: 'Jauns uzdevums: '.$this->title,
                body: $body,
                attachments: $this->attachments()->get(),
            );
        } catch (\Throwable $e) {
            Log::warning('Task assignment email failed', ['task' => $this->id, 'error' => $e->getMessage()]);

            return 'Paziņojumu neizdevās nosūtīt.';
        }

        return null;
    }
}
