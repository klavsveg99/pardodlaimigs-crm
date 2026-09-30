<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Clients\ClientStatusSync;
use Illuminate\Console\Command;

/**
 * Drošības tīkls: pārrēķina visu klientu statusus no to darījumiem.
 * Ikdienas darbība notiek uzreiz (īpašuma statusa maiņa, piesaistīšana),
 * bet šī komanda novērš jebkādu noslīdēšanu.
 */
class SyncClientStatuses extends Command
{
    protected $signature = 'pdc:sync-client-statuses';

    protected $description = 'Pārrēķina klientu statusus (Laimīgs/Aktīvs) no to darījumiem.';

    public function handle(ClientStatusSync $sync): int
    {
        $changed = $sync->syncAll();

        $this->info("Atjaunināti {$changed} klientu statusi.");

        return self::SUCCESS;
    }
}
