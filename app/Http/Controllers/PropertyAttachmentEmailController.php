<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CrmProperty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Attachment upload + "Nosūtīt" email for properties. Mirrors the client
 * attachment system, but the recipient is one of the property's associated
 * clients (chosen in the popup when the property has several).
 */
class PropertyAttachmentEmailController extends Controller
{
    private function authorizeProperty(Request $request, CrmProperty $property): bool
    {
        $user = $request->user();

        return $user->can('manage') || $property->owner_user_id === $user->id;
    }

    /**
     * Upload endpoint: creates the Attachment record immediately so new
     * files are sendable right away and can never be lost on reload.
     */
    public function upload(Request $request, string $propertySlug): JsonResponse
    {
        $property = CrmProperty::query()->where('slug', $propertySlug)->first();
        if (! $property) {
            return response()->json(['message' => 'Īpašums nav atrasts.'], 404);
        }
        if (! $this->authorizeProperty($request, $property)) {
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
        $disk = Storage::disk('public');
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

        $attachment = $property->attachments()->create([
            'path' => $path,
            'disk' => 'public',
            'original_name' => $originalName,
            'mime_type' => $file->getMimeType(),
            'size' => $size,
            'sort_order' => (int) $property->attachments()->max('sort_order') + 1,
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

    public function send(Request $request, string $propertySlug): JsonResponse
    {
        $property = CrmProperty::query()->where('slug', $propertySlug)->first();
        if (! $property) {
            return response()->json(['message' => 'Īpašums nav atrasts.'], 404);
        }
        if (! $this->authorizeProperty($request, $property)) {
            return response()->json(['message' => 'Nav piekļuves.'], 403);
        }

        $data = Validator::make($request->all(), [
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:100'],
            'client_id' => ['required', 'integer'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['integer', 'exists:attachments,id'],
            'body' => ['required', 'string', 'max:100000'],
        ])->validate();

        // The chosen client must actually be associated with this property.
        $client = $property->clients()->whereKey($data['client_id'])->first();
        if (! $client) {
            return response()->json(['message' => 'Izvēlētais klients nav saistīts ar šo īpašumu.'], 422);
        }

        $selected = $property->attachments()
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

        $html = ClientAttachmentEmailController::renderEmailHtml($data['body']);
        $to = trim($data['to']);
        $subject = trim($data['subject']);
        $fromName = trim((string) ($data['from_name'] ?? '')) ?: (string) config('mail.from.name');

        try {
            $disk = Storage::disk('public');

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
            'property_id' => $property->id,
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
}
