<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CrmProperty;
use App\Services\AuditLogger;
use App\Services\ImageOptimizer;
use App\Services\Mail\EmailSender;
use App\Services\Mail\EmailTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
                $result = app(ImageOptimizer::class)->optimize($abs);
                $size = (int) ($result['size'] ?? $size);
            }
        } catch (\Throwable) {
            // Optimisation must never block the upload.
        }

        $attachment = $property->attachments()->create([
            'collection' => 'documents',
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

    public function destroy(Request $request, string $propertySlug, int $attachment): JsonResponse
    {
        $property = CrmProperty::query()->where('slug', $propertySlug)->first();
        if (! $property) {
            return response()->json(['message' => 'Īpašums nav atrasts.'], 404);
        }
        if (! $this->authorizeProperty($request, $property)) {
            return response()->json(['message' => 'Nav piekļuves.'], 403);
        }

        $record = $property->attachments()->whereKey($attachment)->first();
        if (! $record) {
            return response()->json(['message' => 'Fails nav atrasts.'], 404);
        }

        Storage::disk($record->disk)->delete($record->path);
        $record->delete();

        return response()->json(['ok' => true]);
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

        $to = trim($data['to']);
        $subject = trim($data['subject']);
        $fromName = (string) ($request->user()?->name ?? '');

        try {
            app(EmailSender::class)->send($to, $subject, $data['body'], $selected, $fromName);
        } catch (EmailTooLargeException $e) {
            return response()->json(['message' => $e->userMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Nosūtīšana neizdevās — '.$e->getMessage()], 500);
        }

        app(AuditLogger::class)->activity('attachment_email_sent', [
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

    /**
     * "Nosūtīt" e-pasts saistītajam klientam (bez pielikumiem) — tiek izmantots
     * "Paldies par sadarbību" pateicības e-pastam īpašuma skata lapā.
     */
    public function sendToClient(Request $request, string $propertySlug, int $client): JsonResponse
    {
        $property = CrmProperty::query()->where('slug', $propertySlug)->first();
        if (! $property) {
            return response()->json(['message' => 'Īpašums nav atrasts.'], 404);
        }
        if (! $this->authorizeProperty($request, $property)) {
            return response()->json(['message' => 'Nav piekļuves.'], 403);
        }

        $clientModel = $property->clients()->whereKey($client)->first();
        if (! $clientModel) {
            return response()->json(['message' => 'Izvēlētais klients nav saistīts ar šo īpašumu.'], 422);
        }

        $data = Validator::make($request->all(), [
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:100000'],
        ])->validate();

        $to = trim($data['to']);
        $subject = trim($data['subject']);
        $fromName = (string) ($request->user()?->name ?? '');

        try {
            app(EmailSender::class)->send($to, $subject, $data['body'], [], $fromName);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Nosūtīšana neizdevās — '.$e->getMessage()], 500);
        }

        app(AuditLogger::class)->activity('client_email_sent', [
            'client_id' => $clientModel->id,
            'property_id' => $property->id,
            'to' => $to,
            'subject' => $subject,
        ]);

        return response()->json([
            'ok' => true,
            'to' => $to,
            'sentAt' => now()->format('d.m.Y'),
        ]);
    }
}
