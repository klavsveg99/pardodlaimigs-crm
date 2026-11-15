<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RunPropertySearch;
use App\Models\PropertySearch;
use Illuminate\Console\Command;

class ScrapePropertySearches extends Command
{
    protected $signature = 'pdc:scrape-property-searches {--search= : Run one saved search by id}';

    protected $description = 'Queue the active ss.lv and city24.lv saved searches that are due.';

    public function handle(): int
    {
        $query = PropertySearch::query()->where('is_active', true);

        if ($id = $this->option('search')) {
            // Explicit id runs now and ignores the automatic frequency.
            $searches = $query->whereKey((int) $id)->get();
        } else {
            $searches = $query
                ->where('frequency', '!=', 'manual')
                ->get()
                ->filter(fn (PropertySearch $search): bool => $search->isDue())
                ->values();
        }

        foreach ($searches as $search) {
            // Mark the attempt before queueing: the queue worker drains on the
            // same cron ping, but if it lags the next tick must not dispatch
            // the same search again.
            $search->forceFill(['last_run_at' => now()])->save();
            RunPropertySearch::dispatch($search->id);
        }

        $this->info('Ieplānotas meklēšanas: '.$searches->count().'.');

        return self::SUCCESS;
    }
}
