<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Manual "Nosūtīt paziņojumu" for a task. Assignment emails are never sent
 * automatically — the agent presses a button in the task page section.
 */
class TaskNotificationController extends Controller
{
    public function agent(Request $request, string $id): RedirectResponse|JsonResponse
    {
        return $this->send($request, (int) $id, 'agent');
    }

    public function izpilditajs(Request $request, string $id): RedirectResponse|JsonResponse
    {
        return $this->send($request, (int) $id, 'izpilditajs');
    }

    private function send(Request $request, int $id, string $recipient): RedirectResponse|JsonResponse
    {
        $task = Task::query()->find($id);
        if (! $task) {
            abort(404);
        }

        /** @var \App\Models\User $user */
        $user = $request->user();
        if (! $user->can('manage') && $task->assigned_user_id !== $user->id) {
            abort(403);
        }

        $error = $recipient === 'agent'
            ? $task->sendAssignmentNotificationToAgent()
            : $task->sendAssignmentNotificationToIzpilditajs();

        $to = $recipient === 'agent'
            ? $task->assignedTo?->email
            : $task->izpilditajs?->email;

        $message = $error ?? 'Paziņojums nosūtīts uz '.$to.'.';

        // Lapas pārlāde nav vajadzīga — poga tiek aizstāta ar "Nosūtīts"
        // uzreiz (fetch), tāpēc atgriežam JSON.
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => $error === null,
                'recipient' => $recipient,
                'message' => $message,
                'to' => $to,
            ], $error === null ? 200 : 422);
        }

        return redirect()
            ->route('filament.admin.resources.tasks.edit', ['record' => $task->getKey()])
            ->with('task_notify_result', [
                'ok' => $error === null,
                'recipient' => $recipient,
                'message' => $message,
            ]);
    }
}
