<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ClientCrmProperty extends Pivot
{
    protected $table = 'client_crm_properties';

    protected $fillable = ['client_id', 'crm_property_id', 'relation', 'notes_md'];

    public function getRelationLabelAttribute(): string
    {
        return match ($this->relation) {
            'seller' => 'Pārdevējs',
            'buyer' => 'Pircējs',
            'tenant' => 'Īrnieks',
            'landlord' => 'Īzīrētājs',
            'interested' => 'Interesents',
            'contacted' => 'Sazināts',
            default => ucfirst($this->relation),
        };
    }
}
