<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Services\Clients\ClientStatusSync;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClientCrmProperty extends Pivot
{
    protected $table = 'client_crm_properties';

    protected $fillable = ['client_id', 'crm_property_id', 'relation', 'notes_md'];

    protected static function booted(): void
    {
        // Piesaistot/noņemot īpašumu, klienta statuss var mainīties
        // (Laimīgs ↔ Aktīvs).
        $sync = function (self $pivot): void {
            $client = Client::find($pivot->client_id);
            if ($client) {
                app(ClientStatusSync::class)->sync($client);
            }
        };

        static::saved($sync);
        static::deleted($sync);
    }

    public const RELATIONS = [
        'seller' => 'Pārdevējs',
        'buyer' => 'Pircējs',
        'tenant' => 'Īrnieks',
        'landlord' => 'Izīrētājs',
        'interested' => 'Interesents',
        'contacted' => 'Sazināts',
    ];

    public function getRelationLabelAttribute(): string
    {
        return self::RELATIONS[$this->relation] ?? ucfirst($this->relation);
    }
}
