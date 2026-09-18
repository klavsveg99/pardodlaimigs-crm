<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\Mail\EmailSender;
use App\Services\Mail\EmailTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * "Nosūtīt" email popup for client attachments. Opened from the
 * attachment rows (see attachments-grid.blade.php) — never a header bar.
 */
class ClientAttachmentEmailController extends Controller
{
    /**
     * Upload endpoint for client attachments. Creates the Attachment record
     * immediately (files are never just "pending"), so the UI can offer
     * actions like "Nosūtīt" right away and nothing can be lost on reload.
     */
    public function upload(Request $request, string $clientSlug): JsonResponse
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

        $file = $request->file('file');
        if (! $file) {
            return response()->json(['message' => 'Nav faila.'], 422);
        }

        $acceptedTypes = config('attachments.accepted_file_types', []);
        $maxSize = (int) config('attachments.max_size_kb', 25600);

        if (! empty($acceptedTypes) && ! in_array($file->getMimeType(), $acceptedTypes, true)) {
            return response()->json(['message' => 'Šāda faila tipa augšupielāde nav atļauta.'], 422);
        }
        if ($file->getSize() > $maxSize * 1024) {
            return response()->json(['message' => 'Fails ir pārāk liels.'], 422);
        }

        $originalName = $file->getClientOriginalName();
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);

        $candidate = 'attachments/'.$originalName;
        if ($disk->exists($candidate)) {
            $i = 1;
            do {
                $candidate = 'attachments/'.$base.'-'.$i.($ext ? '.'.$ext : '');
                $i++;
            } while ($disk->exists($candidate));
        }
        $path = $file->storeAs('attachments', basename($candidate), 'public');

        $size = (int) $disk->size($path);

        try {
            $abs = $disk->path($path);
            if (is_file($abs) && str_starts_with((string) $file->getMimeType(), 'image/')) {
                $result = app(\App\Services\ImageOptimizer::class)->optimize($abs);
                $size = (int) ($result['size'] ?? $size);
            }
        } catch (\Throwable) {
            // Optimisation must never block the upload.
        }

        $attachment = $client->attachments()->create([
            'collection' => 'gallery',
            'path' => $path,
            'disk' => 'public',
            'original_name' => $originalName,
            'mime_type' => $file->getMimeType(),
            'size' => $size,
            'sort_order' => (int) $client->attachments()->max('sort_order') + 1,
        ]);

        return response()->json([
            'id' => $attachment->id,
            'path' => $path,
            'url' => $disk->url($path),
            'name' => $originalName,
            'size' => $size,
            'created' => $attachment->created_at?->format('d.m.Y'),
        ]);
    }

    /**
     * Delete a client attachment (file + record). Used by the attachment
     * rows so removal works on the view page too, where Filament does not
     * save form state.
     */
    public function destroy(Request $request, string $clientSlug, int $attachment): JsonResponse
    {
        /** @var \App\Models\Client|null $client */
        $client = Client::query()->where('slug', $clientSlug)->first();
        if (! $client) {
            return response()->json(['message' => 'Klients nav atrasts.'], 404);
        }

        $user = $request->user();
        if (! $user->can('manage') && $client->owner_user_id !== $user->id) {
            return response()->json(['message' => 'Nav piekļuves.'], 403);
        }

        $record = $client->attachments()->whereKey($attachment)->first();
        if (! $record) {
            return response()->json(['message' => 'Fails nav atrasts.'], 404);
        }

        \Illuminate\Support\Facades\Storage::disk($record->disk)->delete($record->path);
        $record->delete();

        return response()->json(['ok' => true]);
    }

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

        $to = trim($data['to']);
        $subject = trim($data['subject']);
        $fromName = trim((string) ($data['from_name'] ?? '')) ?: null;

        try {
            app(EmailSender::class)->send($to, $subject, $data['body'], $selected, $fromName);
        } catch (EmailTooLargeException $e) {
            return response()->json(['message' => $e->userMessage()], 422);
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
     * @deprecated Use EmailSender::renderHtml() — kept so existing callers
     * (e.g. PropertyAttachmentEmailController) keep working during migration.
     */
    public static function renderEmailHtml(string $body): string
    {
        return EmailSender::renderHtml($body);
    }
}
