<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Models\Task;
use App\Models\Viewing;
use Illuminate\Support\Collection;

final class CalendarEvents
{
    public function all(): Collection
    {
        return $this->viewings()->concat($this->tasks())->values();
    }

    public function agentOptions(): array
    {
        return $this->all()
            ->filter(fn (array $event) => $event['agentId'] !== null)
            ->mapWithKeys(fn (array $event) => [$event['agentId'] => $event['agent']])
            ->sort()
            ->all();
    }

    private function viewings(): Collection
    {
        return Viewing::with(['property', 'client', 'agent'])
            ->whereNotNull('scheduled_at')
            ->get()
            ->map(fn (Viewing $viewing): array => [
                'id' => 'viewing-'.$viewing->id,
                'type' => 'viewing',
                'title' => $viewing->property?->title ?? '—',
                'client' => $viewing->client?->name ?? '—',
                'agent' => $viewing->agent?->name ?? '—',
                'agentId' => $viewing->agent_user_id,
                'start' => $viewing->scheduled_at?->toIso8601String(),
                'end' => $viewing->scheduled_at?->copy()->addMinutes($viewing->duration_min ?? 30)->toIso8601String(),
                'status' => $viewing->status,
                'color' => match ($viewing->status) {
                    'scheduled' => '#236D63',
                    'done' => '#0f7d60',
                    'cancelled' => '#cf2e2e',
                    'no_show' => '#966830',
                    default => '#414042',
                },
                'url' => route('filament.admin.resources.viewings.edit', $viewing),
            ]);
    }

    private function tasks(): Collection
    {
        return Task::with(['assignedTo', 'client'])
            ->whereNotNull('due_at')
            ->get()
            ->map(fn (Task $task): array => [
                'id' => 'task-'.$task->id,
                'type' => 'task',
                'title' => $task->title,
                'client' => $task->client?->name ?? '—',
                'agent' => $task->assignedTo?->name ?? '—',
                'agentId' => $task->assigned_user_id,
                'start' => $task->due_at?->toIso8601String(),
                'end' => $task->due_at?->toIso8601String(),
                'status' => $task->completed_at ? 'done' : ($task->isOverdue() ? 'overdue' : 'open'),
                'color' => $task->completed_at ? '#9ca3af' : ($task->isOverdue() ? '#cf2e2e' : '#285854'),
                'url' => route('filament.admin.resources.tasks.edit', $task),
            ]);
    }
}
