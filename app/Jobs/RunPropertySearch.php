<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\PropertySearch;
use App\Services\Scraping\PropertySearchRunner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunPropertySearch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 60;

    public function __construct(public int $searchId) {}

    public function handle(PropertySearchRunner $runner): void
    {
        $search = PropertySearch::find($this->searchId);

        if ($search === null || ! $search->is_active) {
            return;
        }

        $runner->run($search);
    }
}
