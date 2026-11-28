<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WpformEntry;
use App\Services\Wpform\WpformEntryNotifier;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Emails the new contact form submission without delaying the public form. */
class NotifyWpformEntry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $entryId) {}

    public function handle(WpformEntryNotifier $notifier): void
    {
        // A queue worker has no current panel; setting it lets the email use
        // the resource's CRM URL.
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $entry = WpformEntry::query()
            ->with('property.owner')
            ->find($this->entryId);

        if ($entry !== null) {
            $notifier->send($entry);
        }
    }
}
