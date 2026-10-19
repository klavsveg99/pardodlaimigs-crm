<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmProperty;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CrmPropertyFeedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $this->hasValidApiKey($request)) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $properties = CrmProperty::query()
            ->with('owner')
            ->orderBy('updated_at', 'desc')
            ->get();

        $maxSort = $properties->max(fn (CrmProperty $p) => $p->sort_order ?? $p->id) ?? 0;

        $properties = $properties->map(function (CrmProperty $p) use ($maxSort) {
            $payload = $p->toWpPayload();
            $payload['sort_order'] = $maxSort - ($p->sort_order ?? $p->id) + 1;

            return $payload;
        });

        return response()->json([
            'properties' => $properties,
            'total' => $properties->count(),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        if (! $this->hasValidApiKey($request)) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $property = CrmProperty::with('owner')->findOrFail($id);

        return response()->json([
            'property' => $property->toWpPayload(),
        ]);
    }

    /**
     * Public slug -> status map for the new frontend (pardodlaimigs.lv/new/).
     * No API key: a listing page only needs to know which properties are
     * reserved so it can badge them and sort them last. Deleted properties
     * are omitted; everything else is returned so the site can hide anything
     * that is no longer published.
     */
    public function publicStatuses(): JsonResponse
    {
        $properties = CrmProperty::query()
            ->where('status', '!=', 'deleted')
            ->get(['slug', 'status'])
            ->map(fn (CrmProperty $property): array => [
                'slug' => (string) $property->slug,
                'status' => (string) $property->status,
            ])
            ->values();

        return response()
            ->json(['properties' => $properties])
            ->header('Cache-Control', 'no-store, max-age=0');
    }

    public function agents(Request $request): JsonResponse
    {
        if (! $this->hasValidApiKey($request)) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $users = User::query()
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'role' => $u->role,
                'phone' => $u->phone,
                'position' => $u->position,
                'description' => $u->description,
                'avatar_url' => $u->avatar_url,
                'facebook_url' => $u->facebook_url,
                'instagram_url' => $u->instagram_url,
                'linkedin_url' => $u->linkedin_url,
                'website_url' => $u->website_url,
                'office_address' => $u->office_address,
            ]);

        return response()->json([
            'agents' => $users,
        ]);
    }

    /**
     * WP sinhronizācija pēc katra upsert atsūta atpakaļ saiti
     * (WP post ID + faktiskais post_name), lai CRM "Skatīt" poga vienmēr
     * vestu uz īsto ierakstu. Raksta tikai izmainītos laukus, lai nerastos
     * lieki audit ieraksti un updated_at izmaiņas.
     */
    public function link(Request $request): JsonResponse
    {
        if (! $this->hasValidApiKey($request)) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $request->validate([
            'crm_id' => 'required|integer|min:1',
            'wp_post_id' => 'required|integer|min:1',
            'slug' => 'nullable|string|max:255',
        ]);

        $property = CrmProperty::find($data['crm_id']);
        if (! $property) {
            return response()->json(['message' => 'Property not found.'], Response::HTTP_NOT_FOUND);
        }

        $updates = [];
        if ((int) ($property->wp_post_id ?? 0) !== (int) $data['wp_post_id']) {
            $updates['wp_post_id'] = (int) $data['wp_post_id'];
        }
        if (filled($data['slug'] ?? null) && $property->slug !== $data['slug']) {
            $updates['slug'] = $data['slug'];
        }

        if ($updates !== []) {
            $property->update($updates);
        }

        return response()->json(['ok' => true, 'public_url' => $property->public_url]);
    }

    private function hasValidApiKey(Request $request): bool
    {
        $expected = (string) config('wp-bridge.wordpress.api_key');
        $provided = (string) $request->header('X-CRM-API-Key');

        return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
    }
}
