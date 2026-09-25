<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\OverdueTaskReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SendReminders extends Command
{
    protected $signature = 'pdc:send-reminders';

    protected $description = 'Send reminder emails for overdue tasks.';

    public function handle(): int
    {
        $sent = 0;

        $overdueTasks = Task::query()
            ->whereNull('completed_at')
            ->whereNotNull('assigned_user_id')
            ->where('due_at', '<', now()->endOfDay())
            ->get()
            ->filter(fn (Task $task): bool => $task->isOverdue());

        foreach ($overdueTasks as $task) {
            if ($this->throttle('task', $task->id)) {
                $task->assignedTo->notify(new OverdueTaskReminder($task));
                $sent++;
            }
        }

        $this->info("Sent {$sent} reminder(s).");

        return 0;
    }

    protected function throttle(string $type, int $id): bool
    {
        $key = "reminder:{$type}:{$id}";

        if (Cache::has($key)) {
            return false;
        }

        Cache::put($key, true, Carbon::now()->addWeek());

        return true;
    }
}
