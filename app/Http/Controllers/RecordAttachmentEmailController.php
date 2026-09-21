<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Task;
use App\Models\Viewing;
use App\Services\AuditLogger;
use App\Services\ImageOptimizer;
use App\Services\Mail\EmailSender;
use App\Services\Mail\EmailTooLargeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Attachment upload/delete + "Nosūtīt" email for records that belong to a
 * client (viewings and tasks). Mirrors the client/property systems: the
 * recipient is the record's associated client, and sends are hidden when
 * there is no client.
 */
class RecordAttachmentEmailController extends Controller
{
    public function uploadViewing(Request $request, string $id): JsonResponse
    {
        return $this->upload($request, 'viewing', (int) $id);
    }

    public function uploadTask(Request $request, string $id): JsonResponse
    {
        return $this->upload($request, 'task', (int) $id);
    }

    public function destroyViewing(Request $request, string $id, string $attachment): JsonResponse
    {
        return $this->destroy($request, 'viewing', (int) $id, (int) $attachment);
    }

    public function destroyTask(Request $request, string $id, string $attachment): JsonResponse
    {
        return $this->destroy($request, 'task', (int) $id, (int) $attachment);
    }

    public function sendViewing(Request $request, string $id): JsonResponse
    {
        return $this->send($request, 'viewing', (int) $id);
    }

    public function sendTask(Request $request, string $id): JsonResponse
    {
        return $this->send($request, 'task', (int) $id);
    }

    private function resolve(string $type, int $id): ?Model
    {
        return match ($type) {
            'viewing' => Viewing::query()->find($id),
            'task' => Task::query()->find($id),
            default => null,
        };
    }

    private function authorizeRecord(Request $request, Model $record): bool
    {
        $user = $request->user();
        if ($user->can('manage')) {
            return true;
        }

        return match (true) {
            $record instanceof Viewing => $record->agent_user_id === $user->id,
            $record instanceof Task => $record->assigned_user_id === $user->id,
            default => false,
        };
    }

    private function clientFor(Model $record): ?Client
    {
        return $record->client;
    }

    private function notFound(string $type): JsonResponse
    {
        return response()->json(['message' => $type === 'viewing' ? 'Apskate nav atrasta.' : 'Uzdevums nav atrasts.'], 404);
    }

    private function upload(Request $request, string $type, int $id): JsonResponse
    {
        $record = $this->resolve($type, $id);
        if (! $record) {
            return $this->notFound($type);
        }
        if (! $this->authorizeRecord($request, $record)) {
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

        $attachment = $record->attachments()->create([
            'collection' => 'gallery',
            'path' => $path,
            'disk' => 'public',
            'original_name' => $originalName,
            'mime_type' => $file->getMimeType(),
            'size' => $size,
            'sort_order' => (int) $record->attachments()->max('sort_order') + 1,
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

    private function destroy(Request $request, string $type, int $id, int $attachmentId): JsonResponse
    {
        $record = $this->resolve($type, $id);
        if (! $record) {
            return $this->notFound($type);
        }
        if (! $this->authorizeRecord($request, $record)) {
            return response()->json(['message' => 'Nav piekļuves.'], 403);
        }

        $attachment = $record->attachments()->whereKey($attachmentId)->first();
        if (! $attachment) {
            return response()->json(['message' => 'Fails nav atrasts.'], 404);
        }

        Storage::disk($attachment->disk)->delete($attachment->path);
        $attachment->delete();

        return response()->json(['ok' => true]);
    }

    private function send(Request $request, string $type, int $id): JsonResponse
    {
        $record = $this->resolve($type, $id);
        if (! $record) {
            return $this->notFound($type);
        }
        if (! $this->authorizeRecord($request, $record)) {
            return response()->json(['message' => 'Nav piekļuves.'], 403);
        }

        $client = $this->clientFor($record);
        if (! $client) {
            return response()->json(['message' => 'Ar šo ierakstu nav saistīts neviens klients.'], 422);
        }

        $data = Validator::make($request->all(), [
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['integer', 'exists:attachments,id'],
            'body' => ['required', 'string', 'max:100000'],
        ])->validate();

        $selected = $record->attachments()
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
            'owner' => $type.':'.$record->getKey(),
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
        ]);
    }
}
