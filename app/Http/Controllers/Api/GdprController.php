<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Task;
use App\Models\Viewing;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class GdprController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function requestExport(Request $request): JsonResponse
    {
        $email = (string) $request->input('email');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Invalid email'], 422);
        }
        $url = URL::signedRoute('gdpr.export', ['email' => $email]);

        $this->audit->log('export_request', 'client', null, null, ['email' => $email]);

        return response()->json(['url' => $url]);
    }

    public function export(Request $request, string $email): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $clients = Client::withTrashed()->where('email', $email)->get();
        $clientIds = $clients->pluck('id');

        $payload = [
            'client' => $clients,
            'viewings' => Viewing::whereIn('client_id', $clientIds)->get(),
            'tasks' => Task::whereIn('client_id', $clientIds)->get(),
            // CRM properties the client is linked to (relation in the pivot),
            // not the legacy WordPress property cache which is no longer used.
            'properties' => $clients
                ->flatMap(fn (Client $c) => $c->crmProperties)
                ->unique('id')
                ->values(),
        ];

        $this->audit->log('export', 'client', null, null, ['email' => $email, 'size' => strlen(json_encode($payload))]);

        return response()->json($payload);
    }

    public function requestErase(Request $request): JsonResponse
    {
        $email = (string) $request->input('email');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Invalid email'], 422);
        }
        $url = URL::signedRoute('gdpr.erase', ['email' => $email]);

        $this->audit->log('erase_request', 'client', null, null, ['email' => $email]);

        return response()->json(['url' => $url]);
    }

    public function erase(Request $request, string $email): JsonResponse
    {
        if (! $request->hasValidSignature()) {
            abort(403);
        }

        $clients = Client::where('email', $email)->get();
        foreach ($clients as $c) {
            $c->update([
                'name' => '—',
                'phone' => null,
                'email' => null,
                'source' => null,
                'notes_md' => null,
                'gdpr_erased_at' => now(),
                'deleted_at' => now(),
            ]);
            $c->delete(); // soft delete
        }
        $this->audit->log('erase', 'client', null, null, ['email' => $email, 'rows' => $clients->count()]);

        return response()->json(['erased' => $clients->count()]);
    }
}
