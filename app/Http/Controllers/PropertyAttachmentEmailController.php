<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\EmailLog;
use App\Services\AuditLogger;
use App\Services\ImageOptimizer;
use App\Services\Mail\EmailSender;
use App\Services\Mail\EmailTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

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
        if (! $file || ! $file->isValid()) {
            $errorCode = $file?->getError() ?? ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);

            Log::warning('Property attachment upload received no valid file', [
                'property_id' => $property->getKey(),
                'upload_error_code' => $errorCode,
                'content_length' => (int) $request->server('CONTENT_LENGTH', 0),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
            ]);

            return response()->json([
                'message' => 'Fails netika saņemts - '.ClientAttachmentEmailController::uploadErrorMessage($errorCode).'.',
            ], 422);
        }

        $acceptedTypes = config('attachments.accepted_file_types', []);
        $maxSize = (int) config('attachments.max_size_kb', 25600);

        if (! empty($acceptedTypes) && ! in_array($file->getMimeType(), $acceptedTypes, true)) {
            Log::info('Property attachment upload rejected: file type', [
                'property_id' => $property->getKey(),
                'name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            return response()->json([
                'message' => 'Šāda faila tipa augšupielāde nav atļauta: '.$file->getMimeType()
                    .'. Atļautie tipi: '.implode(', ', config('attachments.accepted_mimes', [])).'.',
            ], 422);
        }

        if ($file->getSize() > $maxSize * 1024) {
            Log::info('Property attachment upload rejected: file too large', [
                'property_id' => $property->getKey(),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'limit_kb' => $maxSize,
            ]);

            return response()->json([
                'message' => 'Fails ir pārāk liels: '.ClientAttachmentEmailController::sizeLabel((int) $file->getSize())
                    .' (maksimums '.ClientAttachmentEmailController::sizeLabel($maxSize * 1024).').',
            ], 422);
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

        try {
            $path = $file->storeAs('attachments', basename($candidate), 'public');

            if (! $path) {
                throw new RuntimeException('storeAs returned false for '.basename($candidate));
            }

            $size = (int) $disk->size($path);

            try {
                $abs = $disk->path($path);
                if (is_file($abs) && str_starts_with((string) $file->getMimeType(), 'image/')) {
                    $result = app(ImageOptimizer::class)->optimize($abs);
                    $size = (int) ($result['size'] ?? $size);
                }
            } catch (\Throwable $e) {
                // Optimisation must never block the upload.
                Log::warning('Property attachment image optimization failed', [
                    'property_id' => $property->getKey(),
                    'path' => $path,
                    'exception' => $e,
                ]);
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
        } catch (\Throwable $e) {
            Log::error('Property attachment upload failed', [
                'property_id' => $property->getKey(),
                'name' => $originalName,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'exception' => $e,
            ]);

            return response()->json(['message' => 'Neizdevās augšupielādēt pielikumu - '.$e->getMessage().'.'], 500);
        }

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
            app(EmailSender::class)->send($to, $subject, $data['body'], $selected, $fromName, context: [
                'context' => 'attachment',
                'property_id' => $property->id,
                'client_id' => $client->id,
                'attachment_ids' => $selected->pluck('id')->all(),
            ]);
        } catch (EmailTooLargeException $e) {
            return response()->json(['message' => $e->userMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Nosūtīšana neizdevās - '.$e->getMessage()], 500);
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
            'to_client' => strcasecmp($to, trim((string) $client->email)) === 0,
            'marked' => $selected->pluck('id')->all(),
            'sentAt' => now()->format('d.m.Y'),
            'lastEmail' => EmailLog::lastForAttachment((int) $selected->first()->id)?->preview(),
        ]);
    }

    /**
     * "Nosūtīt" e-pasts saistītajam klientam (bez pielikumiem) - tiek izmantots
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
            app(EmailSender::class)->send($to, $subject, $data['body'], [], $fromName, context: [
                'context' => 'client_thanks',
                'property_id' => $property->id,
                'client_id' => $clientModel->id,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Nosūtīšana neizdevās - '.$e->getMessage()], 500);
        }

        app(AuditLogger::class)->activity('client_email_sent', [
            'client_id' => $clientModel->id,
            'property_id' => $property->id,
            'to' => $to,
            'subject' => $subject,
        ]);

        // Pateicības e-pasta pielāgoto tematu/saturu saglabā lietotāja profilā,
        // lai nākamreiz tas atvērtos ar viņa versiju (nevis noklusējumu).
        $request->user()?->update([
            'client_email_subject' => $subject,
            'client_email_body' => $data['body'],
        ]);

        return response()->json([
            'ok' => true,
            'to' => $to,
            'sentAt' => now()->format('d.m.Y'),
        ]);
    }
}
