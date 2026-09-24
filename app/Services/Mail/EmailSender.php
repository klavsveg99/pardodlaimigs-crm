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

    /** Google review link shown in every outgoing email footer. */
    public const GOOGLE_REVIEW_URL = 'https://g.page/r/CRZd6XZu2hhmEAE/review';

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
        ?string $signature = null,
    ): void {
        $files = collect($attachments);
        $totalSize = (int) $files->sum('size');

        if ($totalSize > self::MAX_ATTACHMENT_BYTES) {
            throw new EmailTooLargeException($totalSize);
        }

        // Paraksts tiek pievienots automātiski: vispirms no lietotāja profila
        // (ja nav padots tieši), citādi e-pasts izskatās vienādi visiem.
        $signature ??= self::defaultSignature();

        $html = self::renderHtml($body, $signature);
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

    /** Lietotāja saglabātais e-pasta paraksts (ja tāds ir). */
    public static function defaultSignature(): ?string
    {
        $signature = auth()->user()?->email_signature;

        return filled($signature) ? (string) $signature : null;
    }

    /**
     * RichEditor-like HTML → email-safe HTML: content is user-authored HTML,
     * strip dangerous elements and wrap it in a minimal branded template.
     *
     * Struktūra: saturs → lietotāja paraksts → atsevišķa kājene ar logotipu
     * un saiti uz pardodlaimigs.lv.
     */
    public static function renderHtml(string $body, ?string $signature = null): string
    {
        $body = self::sanitize($body);
        if (trim($body) === '') {
            $body = '<p></p>';
        }

        $signatureHtml = '';
        if (filled($signature)) {
            $signatureHtml = '<div style="margin-top:18px;padding-top:14px;border-top:1px solid #e2e8e6;'
                .'font-size:14px;line-height:1.6;color:#374151;">'
                .self::sanitize((string) $signature)
                .'</div>';
        }

        $logo = e(url('images/favicon-32x32.jpg'));
        $reviewUrl = e(self::GOOGLE_REVIEW_URL);
        $footer = '<hr style="border:none;border-top:1px solid #e2e8e6;margin:20px 0 12px;">'
            .'<p style="font-size:12px;color:#6b7280;margin:0;">'
            .'<img src="'.$logo.'" alt="Pārdod Laimīgs" width="18" height="18" '
            .'style="vertical-align:middle;margin-right:6px;border-radius:4px;border:0;">'
            .'Pārdod Laimīgs · <a href="https://pardodlaimigs.lv" style="color:#285854;">pardodlaimigs.lv</a>'
            .'</p>'
            .'<p style="font-size:12px;color:#6b7280;margin:6px 0 0;">'
            .'<a href="'.$reviewUrl.'" style="color:#285854;">Atstājiet atsauksmi Google</a>'
            .'</p>';

        return '<!DOCTYPE html><html><head><meta charset="utf-8"></head>'
            .'<body style="margin:0;padding:24px;background:#f5f7f6;">'
            .'<div style="max-width:600px;margin:0 auto;background:#ffffff;border-radius:12px;'
            .'border:1px solid #e2e8e6;padding:24px;font-family:Arial,Helvetica,sans-serif;'
            .'font-size:14px;line-height:1.6;color:#1f2937;">'
            .$body
            .$signatureHtml
            .$footer
            .'</div></body></html>';
    }

    /**
     * Strip scripts/styles/iframes from user-authored HTML — the editors never
     * produce them but keep the email safe.
     */
    private static function sanitize(string $html): string
    {
        $html = trim($html);
        $html = preg_replace('/<\s*(script|style|iframe|object|embed)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $html) ?? $html;

        return preg_replace('/<\s*(script|style|iframe|object|embed)[^>]*\/?>/i', '', $html) ?? $html;
    }
}
