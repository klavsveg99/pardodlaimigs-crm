<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\FollowUpLead;
use App\Notifications\FollowUpReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Iknedēļas (throttled) atgādinājumi par Follow Up līdiem, kam pienācis
 * nākamā kontakta datums. Datums NETIEK pārbīdīts automātiski — līdis paliek
 * "Nokavēts" un redzams, līdz aģents veic darbību "Sazinājos".
 */
class SendFollowUpReminders extends Command
{
    protected $signature = 'pdc:send-followup-reminders';

    protected $description = 'Send reminder emails for due Follow Up leads.';

    public function handle(): int
    {
        $sent = 0;

        $leads = FollowUpLead::query()
            ->due()
            ->whereNotNull('owner_user_id')
            ->with(['owner', 'client', 'property'])
            ->get();

        foreach ($leads as $lead) {
            if (! $lead->owner || blank($lead->owner->email)) {
                continue;
            }

            if (! $this->throttle('followup', $lead->id)) {
                continue;
            }

            $lead->owner->notify(new FollowUpReminder($lead));
            $sent++;
        }

        $this->info("Sent {$sent} follow-up reminder(s).");

        return 0;
    }

    protected function throttle(string $type, int $id): bool
    {
        $key = $type === 'followup' ? FollowUpLead::reminderCacheKey($id) : "reminder:{$type}:{$id}";

        if (Cache::has($key)) {
            return false;
        }

        Cache::put($key, true, Carbon::now()->addWeek());

        return true;
    }
}
