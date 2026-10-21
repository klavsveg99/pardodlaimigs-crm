<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
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

/**
 * "Nosūtīt" email popup for client attachments. Opened from the
 * attachment rows (see attachments-grid.blade.php) - never a header bar.
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
        /** @var Client $client */
        $client = Client::query()->where('slug', $clientSlug)->first();
        if (! $client) {
            return response()->json(['message' => 'Klients nav atrasts.'], 404);
        }

        $user = $request->user();
        if (! $user->can('manage') && $client->owner_user_id !== $user->id) {
            return response()->json(['message' => 'Nav piekļuves.'], 403);
        }

        $file = $request->file('file');
        if (! $file || ! $file->isValid()) {
            $errorCode = $file?->getError() ?? ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE);

            Log::warning('Client attachment upload received no valid file', [
                'client_id' => $client->getKey(),
                'upload_error_code' => $errorCode,
                'content_length' => (int) $request->server('CONTENT_LENGTH', 0),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
            ]);

            return response()->json(['message' => 'Fails netika saņemts - '.self::uploadErrorMessage($errorCode).'.'], 422);
        }

        $acceptedTypes = config('attachments.accepted_file_types', []);
        $maxSize = (int) config('attachments.max_size_kb', 25600);

        if (! empty($acceptedTypes) && ! in_array($file->getMimeType(), $acceptedTypes, true)) {
            Log::info('Client attachment upload rejected: file type', [
                'client_id' => $client->getKey(),
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
            Log::info('Client attachment upload rejected: file too large', [
                'client_id' => $client->getKey(),
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'limit_kb' => $maxSize,
            ]);

            return response()->json([
                'message' => 'Fails ir pārāk liels: '.self::sizeLabel((int) $file->getSize())
                    .' (maksimums '.self::sizeLabel($maxSize * 1024).').',
            ], 422);
        }

        try {
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
            if (! $path) {
                throw new \RuntimeException('Attachment could not be stored.');
            }

            $size = (int) $disk->size($path);

            try {
                $abs = $disk->path($path);
                if (is_file($abs) && str_starts_with((string) $file->getMimeType(), 'image/')) {
                    $result = app(ImageOptimizer::class)->optimize($abs);
                    $size = (int) ($result['size'] ?? $size);
                }
            } catch (\Throwable $e) {
                Log::warning('Client attachment image optimization failed', [
                    'client_id' => $client->getKey(),
                    'file_size' => $size,
                    'exception' => $e,
                ]);
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
        } catch (\Throwable $e) {
            Log::error('Client attachment upload failed', [
                'client_id' => $client->getKey(),
                'name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'exception' => $e,
            ]);

            return response()->json(['message' => 'Neizdevās augšupielādēt pielikumu - '.$e->getMessage().'.'], 500);
        }
    }

    /** PHP augšupielādes kļūdas koda skaidrojums (redzams arī lietotājam). */
    public static function uploadErrorMessage(int $errorCode): string
    {
        return match ($errorCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'fails pārsniedz servera limitu ('.ini_get('upload_max_filesize').')',
            UPLOAD_ERR_PARTIAL => 'augšupielāde pārtrūka, pirms fails tika saņemts pilnībā',
            UPLOAD_ERR_NO_FILE => 'fails netika nosūtīts',
            UPLOAD_ERR_NO_TMP_DIR => 'serverim nav pagaidu mapes',
            UPLOAD_ERR_CANT_WRITE => 'serveris nevarēja ierakstīt failu diskā',
            UPLOAD_ERR_EXTENSION => 'augšupielādi apturēja PHP paplašinājums',
            default => 'nezināma augšupielādes kļūda (kods '.$errorCode.')',
        };
    }

    /** Faila izmērs cilvēkam lasāmā formā (MB lieliem, KB maziem failiem). */
    public static function sizeLabel(int $bytes): string
    {
        $value = $bytes >= 1048576
            ? rtrim(rtrim(number_format($bytes / 1048576, 1, ',', ''), '0'), ',').' MB'
            : rtrim(rtrim(number_format($bytes / 1024, 1, ',', ''), '0'), ',').' KB';

        return $value === ' KB' || $value === ' MB' ? '0 '.trim($value) : $value;
    }

    /**
     * Delete a client attachment (file + record). Used by the attachment
     * rows so removal works on the view page too, where Filament does not
     * save form state.
     */
    public function destroy(Request $request, string $clientSlug, int $attachment): JsonResponse
    {
        /** @var Client|null $client */
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

        Storage::disk($record->disk)->delete($record->path);
        $record->delete();

        return response()->json(['ok' => true]);
    }

    public function send(Request $request, string $clientSlug): JsonResponse
    {
        /** @var Client $client */
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
        $fromName = (string) ($request->user()?->name ?? '');

        try {
            app(EmailSender::class)->send($to, $subject, $data['body'], $selected, $fromName, context: [
                'context' => 'attachment',
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
     * @deprecated Use EmailSender::renderHtml() - kept so existing callers
     * (e.g. PropertyAttachmentEmailController) keep working during migration.
     */
    public static function renderEmailHtml(string $body): string
    {
        return EmailSender::renderHtml($body);
    }
}
