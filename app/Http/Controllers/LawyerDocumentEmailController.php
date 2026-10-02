<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Admin\Actions\LawyerDocumentAction;
use App\Models\CrmProperty;
use App\Services\Lawyer\LawyerDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * "Jurista dokuments" 2. soļa e-pasts. Izmanto to pašu klienta pielikumu
 * modāli (attachment-send-popup, variant=lawyer), tāpēc šeit ir tikai
 * priekšskatījuma un nosūtīšanas galapunkti, kas strādā ar tās pašas
 * darbības formas datiem, ko modālis atsūta līdzi.
 */
class LawyerDocumentEmailController extends Controller
{
    private function property(Request $request, string $propertySlug): ?CrmProperty
    {
        $property = CrmProperty::query()->where('slug', $propertySlug)->first();

        if (! $property) {
            return null;
        }

        $user = $request->user();

        return ($user->can('manage') || $property->owner_user_id === $user->id) ? $property : null;
    }

    public function preview(Request $request, string $propertySlug): JsonResponse
    {
        $property = $this->property($request, $propertySlug);
        if (! $property) {
            return response()->json(['message' => 'Īpašums nav atrasts.'], 404);
        }

        $data = [
            'document_type' => (string) $request->input('form.document_type', ''),
            'jurist_id' => $request->input('form.jurist_id'),
            'legal' => (array) $request->input('form.legal', []),
            'seller' => (array) $request->input('form.seller', []),
            'buyer' => (array) $request->input('form.buyer', []),
            'property' => (array) $request->input('form.property', []),
            'attachment_ids' => (array) $request->input('files', []),
            'extra_info' => (string) $request->input('extra_info', ''),
        ];

        $preview = LawyerDocumentAction::emailPreview($property, $data);

        return response()->json([
            'ok' => true,
            'html' => $preview['html'],
            'to' => $preview['to'],
            'subject' => $preview['subject'],
            'attachments' => $preview['attachments'],
        ]);
    }

    public function send(Request $request, string $propertySlug): JsonResponse
    {
        $property = $this->property($request, $propertySlug);
        if (! $property) {
            return response()->json(['message' => 'Īpašums nav atrasts.'], 404);
        }

        $data = Validator::make($request->all(), [
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'files' => ['array'],
            'form' => ['array'],
        ])->validate();

        $form = (array) ($data['form'] ?? []);
        $form['attachment_ids'] = (array) ($data['files'] ?? []);
        $form['extra_info'] = (string) $request->input('extra_info', '');

        $jurist = app(LawyerDocumentService::class)->jurist($form['jurist_id'] ?? null);
        if (! $jurist || blank($jurist->email)) {
            return response()->json(['message' => 'Jurists nav atrasts vai tam nav norādīts e-pasts.'], 422);
        }

        $ok = LawyerDocumentAction::sendFor($property, $form, (string) $data['to'], (string) $data['subject']);

        if (! $ok) {
            return response()->json(['message' => 'Nosūtīšana neizdevās.'], 500);
        }

        return response()->json([
            'ok' => true,
            'to' => (string) $data['to'],
            'marked' => array_values(array_map('intval', (array) ($data['files'] ?? []))),
            'sentAt' => now()->format('d.m.Y'),
        ]);
    }
}
