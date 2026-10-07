<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One sent outgoing email. Written by EmailSender for every message so the
 * "Sūtīt vēlreiz" confirmations can show the previous email, and so the CRM
 * keeps a lightweight send history.
 */
class EmailLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'from_name', 'to_email', 'subject', 'body',
        'context', 'client_id', 'property_id', 'task_id', 'viewing_id',
        'recipient_role', 'attachment_ids', 'attachment_count', 'created_at',
    ];

    protected $casts = [
        'attachment_ids' => 'array',
        'created_at' => 'datetime',
    ];

    /** Compact shape used by the resend-confirmation modal in the browser. */
    public function preview(): array
    {
        return [
            'to' => (string) $this->to_email,
            'subject' => (string) $this->subject,
            'content' => self::htmlToText($this->body),
            'sentAt' => $this->created_at?->format('d.m.Y H:i'),
            'attachments' => (int) $this->attachment_count,
        ];
    }

    public static function lastClientThanks(int $propertyId, int $clientId): ?self
    {
        return static::query()
            ->where('context', 'client_thanks')
            ->where('property_id', $propertyId)
            ->where('client_id', $clientId)
            ->latest('id')
            ->first();
    }

    public static function lastTaskNotice(int $taskId, string $role): ?self
    {
        return static::query()
            ->where('context', 'task_notification')
            ->where('task_id', $taskId)
            ->where('recipient_role', $role)
            ->latest('id')
            ->first();
    }

    public static function lastLawyer(int $propertyId): ?self
    {
        return static::query()
            ->where('context', 'lawyer_request')
            ->where('property_id', $propertyId)
            ->latest('id')
            ->first();
    }

    /** The most recent email that included this attachment (any context). */
    public static function lastForAttachment(int $attachmentId): ?self
    {
        return static::query()
            ->where('attachment_ids', 'like', '%"'.$attachmentId.'"%')
            ->latest('id')
            ->first();
    }

    /** Plain-text rendering of stored HTML, safe to show in a preview. */
    public static function htmlToText(?string $html): string
    {
        $html = (string) $html;
        if (trim($html) === '') {
            return '';
        }

        $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $html) ?? $html;
        $text = preg_replace('/<\s*\/\s*(p|div|li|tr|h[1-6])\s*>/i', "\n", $text) ?? $text;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }
}
