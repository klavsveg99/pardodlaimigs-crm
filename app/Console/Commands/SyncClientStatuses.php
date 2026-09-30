<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Client;
use App\Services\Clients\ClientStatusSync;
use Illuminate\Console\Command;

/**
 * Drošības tīkls: pārrēķina visu klientu statusus no to darījumiem un
 * aizpilda trūkstošos dzimšanas datumus no personas kodiem. Ikdienas
 * darbība notiek uzreiz (īpašuma statusa maiņa, piesaistīšana), bet šī
 * komanda novērš jebkādu noslīdēšanu.
 */
class SyncClientStatuses extends Command
{
    protected $signature = 'pdc:sync-client-statuses';

    protected $description = 'Pārrēķina klientu statusus (Laimīgs/Aktīvs) un aizpilda dzimšanas datumus.';

    public function handle(ClientStatusSync $sync): int
    {
        $changed = $sync->syncAll();
        $birthdays = $this->backfillBirthDates();

        $this->info("Atjaunināti {$changed} klientu statusi; aizpildīti {$birthdays} dzimšanas datumi.");

        return self::SUCCESS;
    }

    /** Aizpilda dzimšanas datumu no personas koda, kur tas vēl nav norādīts. */
    protected function backfillBirthDates(): int
    {
        $filled = 0;

        Client::query()
            ->whereNull('birth_date')
            ->whereNotNull('personas_kods')
            ->chunkById(200, function ($clients) use (&$filled): void {
                foreach ($clients as $client) {
                    $date = Client::birthDateFromPersonasKods($client->personas_kods);

                    if ($date) {
                        $client->forceFill(['birth_date' => $date])->saveQuietly();
                        $filled++;
                    }
                }
            });

        return $filled;
    }
}
