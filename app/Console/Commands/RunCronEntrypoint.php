<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Vienīgā cron ieeja uz koplietotā hostinga (Hostinger hPanel cron, ik minūti).
 * WordPress vairs nepastāv, tāpēc neviens cits neiedarbina plānotāju:
 * šī komanda palaiž schedule:run un iztukšo rindu, jo atsevišķa pastāvīga
 * queue worker procesa uz koplietotā hostinga nav.
 */
class RunCronEntrypoint extends Command
{
    protected $signature = 'pdc:cron';

    protected $description = 'Palaiž plānotāju un iztukšo rindu (cron ieeja).';

    public function handle(): int
    {
        $this->call('schedule:run');

        $this->call('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 45,
            '--tries' => 1,
            '--sleep' => 1,
        ]);

        return self::SUCCESS;
    }
}
