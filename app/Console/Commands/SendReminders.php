<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\Viewing;
use App\Notifications\OverdueTaskReminder;
use App\Notifications\OverdueViewingReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SendReminders extends Command
{
    protected $signature = 'pdc:send-reminders';

    protected $description = 'Send reminder emails for overdue tasks and overdue viewings.';

    public function handle(): int
    {
        $sent = 0;

        $overdueTasks = Task::query()
            ->whereNull('completed_at')
            ->where('due_at', '<', now())
            ->whereNotNull('assigned_user_id')
            ->get();

        foreach ($overdueTasks as $task) {
            if ($this->throttle('task', $task->id)) {
                $task->assignedTo->notify(new OverdueTaskReminder($task));
                $sent++;
            }
        }

        $overdueViewings = Viewing::query()
            ->where('scheduled_at', '<', now())
            ->where('status', 'scheduled')
            ->whereNotNull('agent_user_id')
            ->get();

        foreach ($overdueViewings as $viewing) {
            if ($this->throttle('viewing', $viewing->id)) {
                $viewing->agent->notify(new OverdueViewingReminder($viewing));
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
