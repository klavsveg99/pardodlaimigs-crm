<?php

namespace App\Console\Commands;

use App\Jobs\HardDeleteErasedClients as PurgeJob;
use Illuminate\Console\Command;

class HardDeleteErasedClients extends Command {
    protected $signature = 'pdc:gdpr-purge';
    protected $description = 'Hard-delete clients that were soft-erased >30 days ago (GDPR).';

    public function handle(): int {
        PurgeJob::dispatch();
        $this->info('Purge dispatched.');
        return self::SUCCESS;
    }
}
