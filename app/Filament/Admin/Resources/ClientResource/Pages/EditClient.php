<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    use SyncsAttachments;

    protected static string $resource = ClientResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt klientu';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Skatīt'),
        ];
    }
}
