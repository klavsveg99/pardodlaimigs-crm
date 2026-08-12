<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\SyncWpForms as SyncWpFormsJob;
use App\Services\Wp\WpSource;
use Illuminate\Console\Command;

class SyncWpForms extends Command
{
    protected $signature = 'pdc:sync-wpforms {--queue : dispatch as a job instead of running synchronously}';

    protected $description = 'Pull WPForms entries into crm.wpform_entries.';

    public function handle(): int
    {
        $this->info('Dispatching WPForms sync...');
        if ($this->option('queue')) {
            SyncWpFormsJob::dispatch()->onQueue('sync');
            $this->info('Job dispatched to "sync" queue.');

            return self::SUCCESS;
        }
        $this->info('Running synchronously...');
        (new SyncWpFormsJob)->handle(app(WpSource::class));
        $this->info('Done.');

        return self::SUCCESS;
    }
}
