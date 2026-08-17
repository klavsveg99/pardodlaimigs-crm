<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ClientCrmProperty extends Pivot
{
    protected $table = 'client_crm_properties';

    protected $fillable = ['client_id', 'crm_property_id', 'relation', 'notes_md'];

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
