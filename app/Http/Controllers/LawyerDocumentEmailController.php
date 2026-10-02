<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CrmProperty;
use App\Models\Izpilditajs;
use App\Services\Lawyer\LawyerDocumentService;
use App\Services\Mail\EmailTooLargeException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * "Jurista dokuments" 2. soļa e-pasta nosūtīšana. Tāda pati plūsma kā
 * klienta pielikumu e-pastam (EmailSender, info@pardodlaimigs.lv, sūtītāja
 * vārds no lietotāja profila), tikai saturs nāk no 1. soļa datiem un
 * saņēmējs pēc noklusējuma ir izvēlētā jurista e-pasts.
 */
class LawyerDocumentEmailController extends Controller
{
    public function send(Request $request, string $propertySlug): JsonResponse
    {
        $property = CrmProperty::query()->where('slug', $propertySlug)->first();
        if (! $property) {
            return response()->json(['message' => 'Īpašums nav atrasts.'], 404);
        }

        $user = $request->user();
        if (! $user->can('manage') && $property->owner_user_id !== $user->id) {
            return response()->json(['message' => 'Nav piekļuves.'], 403);
        }

        $data = Validator::make($request->all(), [
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'files' => ['required', 'array', 'min:1'],
            'files.*' => ['integer', 'exists:attachments,id'],
            'body' => ['required', 'string', 'max:100000'],
            'extra_info' => ['nullable', 'string', 'max:20000'],
            'jurist_id' => ['nullable', 'integer'],
            'document_type' => ['nullable', 'string'],
        ])->validate();

        $service = app(LawyerDocumentService::class);

        // Jurists: no padotā id vai pēc e-pasta adreses (modālī to var mainīt).
        $jurist = $service->jurist($data['jurist_id'] ?? null)
            ?? Izpilditajs::query()
                ->where('category', 'Jurists')
                ->where('email', 'like', trim($data['to']))
                ->first()
            ?? Izpilditajs::query()->where('category', 'Jurists')->first();

        $extra = trim((string) ($data['extra_info'] ?? ''));
        $body = (string) $data['body'];
        if ($extra !== '') {
            $body .= '<p>'.e($extra).'</p>';
        }

        try {
            $requestModel = $service->send(
                $property->refresh(),
                $jurist,
                (string) ($data['document_type'] ?? ''),
                is_array($property->legal_data) ? $property->legal_data : [],
                $user,
                $data['files'],
                trim($data['to']),
                trim($data['subject']),
                $body,
            );
        } catch (EmailTooLargeException $e) {
            return response()->json(['message' => $e->userMessage()], 422);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['message' => 'Nosūtīšana neizdevās — '.$e->getMessage()], 500);
        }

        return response()->json([
            'ok' => true,
            'to' => $requestModel->recipient_email,
            'subject' => $requestModel->subject,
            'marked' => $data['files'],
            'sentAt' => now()->format('d.m.Y'),
        ]);
    }
}
