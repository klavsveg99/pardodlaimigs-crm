<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RunPropertySearch;
use App\Models\PropertySearch;
use Illuminate\Console\Command;

class ScrapePropertySearches extends Command
{
    protected $signature = 'pdc:scrape-property-searches {--search= : Run one saved search by id}';

    protected $description = 'Queue the active ss.lv and city24.lv saved searches.';

    public function handle(): int
    {
        $query = PropertySearch::query()->where('is_active', true);

        if ($id = $this->option('search')) {
            $query->whereKey((int) $id);
        }

        $searches = $query->get();

        foreach ($searches as $search) {
            RunPropertySearch::dispatch($search->id);
        }

        $this->info('Ieplānotas meklēšanas: '.$searches->count().'.');

        return self::SUCCESS;
    }
}
