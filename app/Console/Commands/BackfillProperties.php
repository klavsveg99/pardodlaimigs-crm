<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ReconcileAllProperties;
use Illuminate\Console\Command;

class BackfillProperties extends Command
{
    protected $signature = 'pdc:backfill {--queue : dispatch as a job instead of running synchronously}';
    protected $description = 'Pull all WP properties into crm.properties_cache.';

    public function handle(): int
    {
        $this->info('Dispatching property reconciliation...');
        if ($this->option('queue')) {
            ReconcileAllProperties::dispatch()->onQueue('sync');
            $this->info('Job dispatched to "sync" queue.');
            return self::SUCCESS;
        }
        $this->info('Running synchronously...');
        (new ReconcileAllProperties)->handle(
            app(\App\Services\Wp\WpSource::class),
            app(\App\Services\Wp\PropertyNormalizer::class),
        );
        $this->info('Done.');
        return self::SUCCESS;
    }
}
