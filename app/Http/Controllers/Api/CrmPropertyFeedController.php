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

        $property = CrmProperty::with('owner')->findOrFail($id);

        return response()->json([
            'property' => $property->toWpPayload(),
        ]);
    }

    public function agents(Request $request): JsonResponse
    {
        if (! $this->hasValidApiKey($request)) {
            return response()->json(['message' => 'Unauthorized.'], Response::HTTP_UNAUTHORIZED);
        }

        $users = User::query()
            ->where('role', 'agent')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]);

        return response()->json([
            'agents' => $users,
        ]);
    }

    private function hasValidApiKey(Request $request): bool
    {
        $expected = (string) config('wp-bridge.wordpress.api_key');
        $provided = (string) $request->header('X-CRM-API-Key');

        return $expected !== '' && $provided !== '' && hash_equals($expected, $provided);
    }
}
