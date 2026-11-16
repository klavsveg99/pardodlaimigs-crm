<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\ScrapedListing;
use App\Services\Mail\EmailSender;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * "Nosūtīt piedāvājumu" for the two offer sources:
 *  - a found Meklētāji listing (external property),
 *  - the "interesenti" clients associated with a CRM property.
 *
 * Both use the shared EmailSender template; every recipient is sent a
 * separate message so addresses are never exposed to each other.
 */
class OfferEmailController extends Controller
{
    public function sendScraped(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isPhoto() || $user->isStudent()) {
            return response()->json(['message' => 'Nav piekļuves.'], 403);
        }

        $data = Validator::make($request->all(), [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.email' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:100000'],
        ])->validate();

        $ids = collect($data['items'])->pluck('id')->map(fn ($id): int => (int) $id)->unique()->values();

        $listings = ScrapedListing::query()
            ->whereIn('id', $ids)
            ->whereNull('duplicate_of_id')
            ->when(
                ! $user->can('manage'),
                fn ($query) => $query->where('user_id', $user->id),
            )
            ->get()
            ->keyBy('id');

        $missing = $ids->reject(fn (int $id): bool => $listings->has($id));

        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'Daži izvēlētie objekti vairs nav pieejami (ID: '.$missing->implode(', ').').',
            ], 422);
        }

        $sent = 0;
        $failures = [];

        foreach ($data['items'] as $item) {
            $listing = $listings->get((int) $item['id']);

            try {
                app(EmailSender::class)->send(
                    trim((string) $item['email']),
                    trim((string) $data['subject']),
                    (string) $data['body'],
                    fromName: (string) $user->name,
                    context: [
                        'context' => 'scraped_offer',
                        'scraped_listing_id' => $listing->id,
                    ],
                );

                $listing->forceFill(['offer_sent_at' => now()])->save();
                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $failures[] = $item['email'].': '.$e->getMessage();
                Log::warning('Scraped listing offer failed', [
                    'scraped_listing_id' => $listing->id,
                    'to' => $item['email'],
                    'exception' => $e,
                ]);
            }
        }

        if ($sent === 0) {
            return response()->json([
                'message' => 'Piedāvājumu nosūtīt neizdevās. '.implode(' | ', array_slice($failures, 0, 3)),
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'sent' => $sent,
            'failed' => $failures,
            'sentAt' => now()->format('d.m.Y'),
            'message' => $sent.' piedāvājums nosūtīts.'.($failures !== [] ? ' Daži neizdevās: '.implode(' | ', array_slice($failures, 0, 3)) : ''),
        ]);
    }

    public function sendProperty(Request $request, string $propertySlug): JsonResponse
    {
        $property = CrmProperty::query()->where('slug', $propertySlug)->first();

        if (! $property) {
            return response()->json(['message' => 'Īpašums nav atrasts.'], 404);
        }

        $user = $request->user();

        if (! $user->can('manage') && $property->owner_user_id !== $user->id) {
            return response()->json(['message' => 'Nav piekļuves.'], 403);
        }

        if ($user->isPhoto() || $user->isStudent()) {
            return response()->json(['message' => 'Nav piekļuves.'], 403);
        }

        $data = Validator::make($request->all(), [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer'],
            'items.*.email' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:100000'],
        ])->validate();

        $clientIds = collect($data['items'])->pluck('id')->map(fn ($id): int => (int) $id)->unique()->values();

        $interested = $property->clients()
            ->wherePivot('relation', 'interested')
            ->whereIn('clients.id', $clientIds)
            ->get()
            ->keyBy('id');

        $missing = $clientIds->reject(fn (int $id): bool => $interested->has($id));

        if ($missing->isNotEmpty()) {
            return response()->json([
                'message' => 'Piedāvājumu var sūtīt tikai šī īpašuma interesentiem. Nav atrasti: '.$missing->implode(', ').'.',
            ], 422);
        }

        $sent = 0;
        $failures = [];

        foreach ($data['items'] as $item) {
            /** @var Client|null $client */
            $client = $interested->get((int) $item['id']);

            if (! $client) {
                continue;
            }

            try {
                app(EmailSender::class)->send(
                    trim((string) $item['email']),
                    trim((string) $data['subject']),
                    (string) $data['body'],
                    fromName: (string) $user->name,
                    context: [
                        'context' => 'property_offer',
                        'property_id' => $property->id,
                        'client_id' => $client->id,
                    ],
                );

                $sent++;
            } catch (\Throwable $e) {
                report($e);
                $failures[] = $item['email'].': '.$e->getMessage();
                Log::warning('Property offer failed', [
                    'property_id' => $property->id,
                    'client_id' => $client->id,
                    'to' => $item['email'],
                    'exception' => $e,
                ]);
            }
        }

        if ($sent === 0) {
            return response()->json([
                'message' => 'Piedāvājumu nosūtīt neizdevās. '.implode(' | ', array_slice($failures, 0, 3)),
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'sent' => $sent,
            'failed' => $failures,
            'sentAt' => now()->format('d.m.Y'),
            'message' => $sent.' piedāvājums nosūtīts.'.($failures !== [] ? ' Daži neizdevās: '.implode(' | ', array_slice($failures, 0, 3)) : ''),
        ]);
    }
}
