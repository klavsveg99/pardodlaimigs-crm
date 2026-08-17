<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Deal;
use App\Models\Task;
use App\Models\Viewing;
use App\Notifications\OverdueTaskReminder;
use App\Notifications\OverdueViewingReminder;
use App\Notifications\StaleDealReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SendReminders extends Command
{
    protected $signature = 'pdc:send-reminders';

    protected $description = 'Send reminder emails for overdue tasks, overdue viewings, and stale deals';

    public function handle(): int
    {
        $sent = 0;

        // ── Overdue tasks ──────────────────────────────────────────
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

        // ── Overdue viewings ───────────────────────────────────────
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

        // ── Stale deals (7+ days no update, not sold) ─────────────
        $staleDeals = Deal::query()
            ->where('stage', '!=', 'pardots')
            ->where('updated_at', '<', now()->subDays(7))
            ->whereNotNull('owner_user_id')
            ->get();

        foreach ($staleDeals as $deal) {
            if ($this->throttle('deal', $deal->id)) {
                $deal->owner->notify(new StaleDealReminder($deal));
                $sent++;
            }
        }

        $this->info("Sent {$sent} reminder(s).");

        return 0;
    }

    /**
     * One reminder per entity per week.
     */
    private function throttle(string $type, int $id): bool
    {
        $key = "reminder:{$type}:{$id}";

        if (Cache::has($key)) {
            return false;
        }

        Cache::put($key, true, Carbon::now()->addWeek());

        return true;
    }
}
