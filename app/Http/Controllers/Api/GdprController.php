<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Deal;
use App\Models\PropertyCache;
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
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Invalid email'], 422);
        }
        $url = URL::signedRoute('gdpr.export', ['email' => $email]);

        $this->audit->log('export_request', 'client', null, null, ['email' => $email]);
        return response()->json(['url' => $url]);
    }

    public function export(Request $request, string $email): JsonResponse
    {
        if (!$request->hasValidSignature()) abort(403);

        $payload = [
            'client'     => Client::withTrashed()->where('email', $email)->get(),
            'deals'      => Deal::whereIn('client_id',
                Client::where('email', $email)->pluck('id'))->get(),
            'viewings'   => Viewing::whereIn('client_id',
                Client::where('email', $email)->pluck('id'))->get(),
            'tasks'      => Task::whereIn('client_id',
                Client::where('email', $email)->pluck('id'))->get(),
            'properties' => PropertyCache::whereIn('id',
                \DB::table('client_properties')
                    ->join('clients', 'clients.id', '=', 'client_properties.client_id')
                    ->where('clients.email', $email)
                    ->pluck('client_properties.property_id')
                )->get(),
        ];

        $this->audit->log('export', 'client', null, null, ['email' => $email, 'size' => strlen(json_encode($payload))]);
        return response()->json($payload);
    }

    public function requestErase(Request $request): JsonResponse
    {
        $email = (string) $request->input('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['error' => 'Invalid email'], 422);
        }
        $url = URL::signedRoute('gdpr.erase', ['email' => $email]);

        $this->audit->log('erase_request', 'client', null, null, ['email' => $email]);
        return response()->json(['url' => $url]);
    }

    public function erase(Request $request, string $email): JsonResponse
    {
        if (!$request->hasValidSignature()) abort(403);

        $clients = Client::where('email', $email)->get();
        foreach ($clients as $c) {
            $c->update([
                'name'           => '—',
                'phone'          => null,
                'email'          => null,
                'source'         => null,
                'notes_md'       => null,
                'gdpr_erased_at' => now(),
                'deleted_at'     => now(),
            ]);
            $c->delete(); // soft delete
        }
        $this->audit->log('erase', 'client', null, null, ['email' => $email, 'rows' => $clients->count()]);
        return response()->json(['erased' => $clients->count()]);
    }
}
