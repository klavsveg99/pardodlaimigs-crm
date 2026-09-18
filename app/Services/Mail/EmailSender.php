<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Models\Attachment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * The project's single outgoing-email system.
 *
 * Every user-facing email (client/property/task/viewing notifications, sent
 * attachment emails, …) goes through here so the template, SMTP mailbox and
 * attachment handling are identical everywhere.
 */
class EmailSender
{
    /** Total attachment limit shared by SMTP and recipient inboxes. */
    public const MAX_ATTACHMENT_BYTES = 18 * 1024 * 1024;

    public function __construct(
        private readonly ?string $fromAddress = null,
        private readonly ?string $fromName = null,
    ) {}

    public function fromAddress(): string
    {
        return $this->fromAddress ?: (string) config('mail.from.address');
    }

    public function fromName(): string
    {
        return $this->fromName ?: (string) config('mail.from.name');
    }

    /**
     * Send an HTML email through the shared template.
     *
     * @param  iterable<Attachment>  $attachments  attachment records to attach
     * @param  string|null  $fromName  editable sender display name
     *
     * @throws EmailTooLargeException when the attachments exceed the limit
     */
    public function send(
        string $to,
        string $subject,
        string $body,
        iterable $attachments = [],
        ?string $fromName = null,
    ): void {
        $files = collect($attachments);
        $totalSize = (int) $files->sum('size');

        if ($totalSize > self::MAX_ATTACHMENT_BYTES) {
            throw new EmailTooLargeException($totalSize);
        }

        $html = self::renderHtml($body);
        $to = trim($to);
        $subject = trim($subject);
        $name = trim((string) $fromName) ?: $this->fromName();

        Mail::html($html, function ($message) use ($to, $subject, $name, $files) {
            $message->from($this->fromAddress(), $name)
                ->subject($subject)
                ->to($to);

            $disk = Storage::disk('public');

            foreach ($files as $file) {
                if (! $file instanceof Attachment) {
                    continue;
                }

                $message->attach($disk->path($file->path), [
                    'as' => $file->original_name,
                    'mime' => $file->mime_type,
                ]);
            }
        });
    }

    /**
     * RichEditor-like HTML → email-safe HTML: content is user-authored HTML,
     * strip dangerous elements and wrap it in a minimal branded template.
     */
    public static function renderHtml(string $body): string
    {
        $body = trim($body);
        if ($body === '') {
            $body = '<p></p>';
        }

        // Strip scripts/styles/iframes — the editor never produces them but
        // keep the email safe.
        $body = preg_replace('/<\s*(script|style|iframe|object|embed)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $body) ?? $body;
        $body = preg_replace('/<\s*(script|style|iframe|object|embed)[^>]*\/?>/i', '', $body) ?? $body;

        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head>'
            .'<body style="margin:0;padding:24px;background:#f5f7f6;">'
            .'<div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;'
            .'border:1px solid #e2e8e6;padding:24px;font-family:Arial,Helvetica,sans-serif;'
            .'font-size:14px;line-height:1.6;color:#1f2937;">'
            .$body
            .'<hr style="border:none;border-top:1px solid #e2e8e6;margin:20px 0 12px;">'
            .'<p style="font-size:12px;color:#6b7280;margin:0;">Pārdod Laimīgs · <a href="https://pardodlaimigs.lv" style="color:#285854;">pardodlaimigs.lv</a></p>'
            .'</div></body></html>';
    }
}
