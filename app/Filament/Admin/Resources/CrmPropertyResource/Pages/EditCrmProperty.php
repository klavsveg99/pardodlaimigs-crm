<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrmProperty extends EditRecord
{
    use SyncsAttachments;

    protected static string $resource = CrmPropertyResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt īpašumu';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Skatīt'),
        ];
    }
}
