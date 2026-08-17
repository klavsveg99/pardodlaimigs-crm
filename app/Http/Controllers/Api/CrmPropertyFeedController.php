<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrmProperty;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmPropertyFeedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $properties = CrmProperty::query()
            ->where('status', '!=', 'draft')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(fn (CrmProperty $p) => $p->toWpPayload());

        return response()->json([
            'properties' => $properties,
            'total' => $properties->count(),
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $property = CrmProperty::findOrFail($id);

        return response()->json([
            'property' => $property->toWpPayload(),
        ]);
    }
}
