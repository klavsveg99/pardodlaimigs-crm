<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\Izpilditajs;
use App\Models\Task;
use App\Models\User;
use App\Models\Viewing;
use App\Services\Calendar\CalendarEvents;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * Kalendāra ātrā pievienošana: uzklikšķinot uz dienas/laika šūnas, tiek
 * izveidots uzdevums vai apskate bez lapas pārlādes. Saraksta izvēlnes tiek
 * ielādētas atsevišķi (options), lai tās netraucētu pārējām lapām.
 */
class CalendarQuickAddController extends Controller
{
    public function options(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user->isPhoto() || $user->isStudent(), 403);

        // Aģentu izvēlne redzama tikai administratoram; aģents vienmēr ir
        // pats veidotājs.
        $agents = $user->can('manage')
            ? User::query()->assignable()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $u): array => ['id' => $u->id, 'name' => $u->name])->all()
            : [];

        return response()->json([
            'agents' => $agents,
            'izpilditajs' => Izpilditajs::query()->orderBy('name')->limit(500)->get()
                ->map(fn (Izpilditajs $i): array => ['id' => $i->id, 'name' => $i->display_label])->all(),
            'clients' => Client::query()->orderBy('name')->limit(1000)->get(['id', 'name'])
                ->map(fn (Client $c): array => ['id' => $c->id, 'name' => $c->name])->all(),
            'properties' => CrmProperty::query()->where('status', '!=', 'deleted')->orderByDesc('id')->limit(1000)->get()
                ->map(fn (CrmProperty $p): array => ['id' => $p->id, 'name' => $p->selection_label])->all(),
            'canPickAgent' => $user->can('manage'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user->isPhoto() || $user->isStudent(), 403);

        $type = (string) $request->input('type', '');

        if ($type === 'task') {
            return $this->storeTask($request, $user);
        }

        if ($type === 'viewing') {
            return $this->storeViewing($request, $user);
        }

        return response()->json(['ok' => false, 'message' => 'Nederīgs ieraksta veids.'], 422);
    }

    private function storeTask(Request $request, User $user): JsonResponse
    {
        $input = $this->nullifyEmpty($request->only([
            'title', 'due_at', 'assigned_user_id', 'izpilditajs_id', 'client_id', 'property_id', 'body',
        ]));

        $validator = Validator::make($input, [
            'title' => ['required', 'string', 'max:255'],
            'due_at' => ['required', 'date'],
            'assigned_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'izpilditajs_id' => ['nullable', 'integer', 'exists:izpilditajs,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
            'property_id' => ['nullable', 'integer', 'exists:crm_properties,id'],
            'body' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();
        // Aģents drīkst piešķirt uzdevumu tikai sev.
        if (! $user->can('manage')) {
            $data['assigned_user_id'] = $user->id;
        }
        $data['assigned_user_id'] ??= $user->id;
        $data['created_by_user_id'] = $user->id;

        $task = Task::create($data);

        return response()->json([
            'ok' => true,
            'event' => app(CalendarEvents::class)->taskEvent($task),
        ]);
    }

    private function storeViewing(Request $request, User $user): JsonResponse
    {
        $input = $this->nullifyEmpty($request->only([
            'property_id', 'client_id', 'scheduled_at', 'duration_min', 'agent_user_id', 'status', 'notes_md',
        ]));

        $validator = Validator::make($input, [
            'property_id' => ['required', 'integer', 'exists:crm_properties,id'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'scheduled_at' => ['required', 'date'],
            'duration_min' => ['nullable', 'integer', 'min:1', 'max:600'],
            'agent_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'in:scheduled,done,cancelled,no_show'],
            'notes_md' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $data = $validator->validated();
        if (! $user->can('manage')) {
            $data['agent_user_id'] = $user->id;
        }
        $data['agent_user_id'] ??= $user->id;
        $data['duration_min'] ??= 30;
        $data['status'] ??= 'scheduled';

        $viewing = Viewing::create($data);

        return response()->json([
            'ok' => true,
            'event' => app(CalendarEvents::class)->viewingEvent($viewing),
        ]);
    }

    /** @return array<string, mixed> */
    private function nullifyEmpty(array $input): array
    {
        return array_map(fn ($value) => $value === '' ? null : $value, $input);
    }

    private function validationError(ValidatorContract $validator): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'message' => 'Pārbaudi ievadītos datus.',
            'errors' => $validator->errors()->toArray(),
        ], 422);
    }
}
