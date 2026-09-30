<?php

declare(strict_types=1);

namespace App\Services\Clients;

use App\Models\Client;
use App\Models\CrmProperty;
use Illuminate\Support\Collection;

/**
 * Uztur klienta statusu sinhronizētu ar viņa darījumiem.
 *
 * "Laimīgs" = klientam ir vismaz viens noslēgts darījums (pārdevējs vai
 * pircējs pārdotam īpašumam) UN šobrīd nav neviena aktīva īpašuma
 * pārdošanā (pārdevēja loma statusā "Pārdošanā"/"Melnraksts"). Līds vienmēr
 * paliek Līds — tas ir aktīvs pārdošanas process.
 */
class ClientStatusSync
{
    public function sync(Client $client): void
    {
        if ($client->trashed()) {
            return;
        }

        $desired = $this->desiredStatus($client);

        if ($client->status !== $desired) {
            $client->status = $desired;
            $client->save();
        }
    }

    /** @param  Collection<int, Client>  $clients */
    public function syncMany(Collection $clients): void
    {
        $clients->each(fn (Client $client) => $this->sync($client));
    }

    public function syncProperty(CrmProperty $property): void
    {
        $this->syncMany(
            $property->clients()
                ->wherePivotIn('relation', ['seller', 'buyer'])
                ->get()
        );
    }

    /** Pārrēķina visus klientus; atgriež mainīto skaitu. */
    public function syncAll(): int
    {
        $changed = 0;

        Client::query()->chunkById(200, function (Collection $clients) use (&$changed): void {
            foreach ($clients as $client) {
                $before = $client->status;
                $this->sync($client);
                if ($client->status !== $before) {
                    $changed++;
                }
            }
        });

        return $changed;
    }

    public function desiredStatus(Client $client): string
    {
        if ($client->status === 'lead') {
            return 'lead';
        }

        $hasClosed = $client->crmProperties()
            ->wherePivotIn('relation', ['seller', 'buyer'])
            ->where('crm_properties.status', 'sold')
            ->exists();

        if (! $hasClosed) {
            return 'active';
        }

        $hasActive = $client->crmProperties()
            ->wherePivot('relation', 'seller')
            ->whereIn('crm_properties.status', ['published', 'draft'])
            ->exists();

        return $hasActive ? 'active' : 'laimigs';
    }
}
