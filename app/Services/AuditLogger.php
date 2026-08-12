<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Deal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function __construct(private readonly Request $request) {}

    public function log(
        string $action,
        string $entity,
        ?int $entityId,
        ?array $before = null,
        ?array $after = null,
    ): AuditLog {
        return AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'before' => $before,
            'after' => $after,
            'ip' => $this->request->ip(),
            'route' => $this->request->route()?->getName() ?? $this->request->path(),
            'created_at' => now(),
        ]);
    }

    public function activity(string $type, ?Deal $deal = null, array $payload = []): Activity
    {
        return Activity::create([
            'actor_user_id' => Auth::id(),
            'deal_id' => $deal?->id,
            'client_id' => $deal?->client_id ?? ($payload['client_id'] ?? null),
            'property_id' => $payload['property_id'] ?? null,
            'type' => $type,
            'payload' => $payload,
            'created_at' => now(),
        ]);
    }
}
