<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmProperty;
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
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn (CrmProperty $p) => $p->toWpPayload());

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

        $property = CrmProperty::findOrFail($id);

        return response()->json([
            'property' => $property->toWpPayload(),
        ]);
    }

    private function hasValidApiKey(Request $request): bool
    {
        $expected = (string) config('wp-bridge.wordpress.api_key');
        $provided = (string) $request->header('X-CRM-API-Key');

        return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
    }
}
