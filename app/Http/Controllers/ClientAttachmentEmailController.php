<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

/**
 * "Nosūtīt" email popup for client attachments. Opened from the
 * attachment rows (see attachments-grid.blade.php) — never a header bar.
 */
class ClientAttachmentEmailController extends Controller
{
    public function send(Request $request, string $clientSlug): JsonResponse
    {
        /** @var \App\Models\Client $client */
        $client = Client::query()->where('slug', $clientSlug)->first();
        if (! $client) {
            return response()->json(['message' => 'Klients nav atrasts.'], 404);
        }

        $user = $request->user();
        if (! $user->can('manage') && $client->owner_user_id !== $user->id) {
            return response()->json(['message' => 'Nav piekļuves.'], 403);
        }

        $data = Validator::make($request->all(), [
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:100'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['integer', 'exists:attachments,id'],
            'body' => ['required', 'string', 'max:100000'],
        ])->validate();

        $selected = $client->attachments()
            ->whereIn('id', $data['files'])
            ->orderBy('sort_order')
            ->get();

        if ($selected->isEmpty()) {
            return response()->json(['message' => 'Faili netika atrasti.'], 422);
        }

        // Total attachment size guard: shared SMTP + recipient limits
        // (base64 inflates payloads by ~37% over the raw size).
        $totalSize = $selected->sum('size');
        if ($totalSize > 18 * 1024 * 1024) {
            return response()->json([
                'message' => 'Pielikumi pārsniedz 18 MB (izvēlēti '
                    .sprintf('%.1f', $totalSize / (1024 * 1024)).' MB). Sūtiet mazāk failu vienā e-pastā.',
            ], 422);
        }

        $html = self::renderEmailHtml($data['body']);
        $to = trim($data['to']);
        $subject = trim($data['subject']);
        // Sender name is editable; the address always stays the configured
        // CRM mailbox (info@pardodlaimigs.lv).
        $fromName = trim((string) ($data['from_name'] ?? '')) ?: (string) config('mail.from.name');

        try {
            $disk = \Illuminate\Support\Facades\Storage::disk('public');

            Mail::html($html, function ($message) use ($to, $subject, $fromName, $selected, $disk) {
                $message->from((string) config('mail.from.address'), $fromName)
                    ->subject($subject)
                    ->to($to);

                foreach ($selected as $file) {
                    $message->attach($disk->path($file->path), [
                        'as' => $file->original_name,
                        'mime' => $file->mime_type,
                    ]);
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Nosūtīšana neizdevās — '.$e->getMessage()], 500);
        }

        app(\App\Services\AuditLogger::class)->activity('attachment_email_sent', [
            'client_id' => $client->id,
            'to' => $to,
            'subject' => $subject,
            'from_name' => $fromName,
            'files' => $selected->pluck('id')->all(),
        ]);

        return response()->json([
            'ok' => true,
            'to' => $to,
            'marked' => $selected->pluck('id')->all(),
            'sentAt' => now()->format('d.m.Y'),
        ]);
    }

    /**
     * RichEditor-like HTML → email-safe HTML: content is user-authored
     * HTML, strip dangerous elements and wrap it in a minimal template.
     */
    public static function renderEmailHtml(string $body): string
    {
        $body = trim((string) $body);
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
