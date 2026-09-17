<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        // Send/email + WhatsApp live only in the attachment rows'
        // "Nosūtīt" popup — never in the page header.
        return [
            Actions\EditAction::make()->label('Rediģēt'),
        ];
    }
}
