<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\DealResource\Pages;

use App\Filament\Admin\Resources\DealResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDeal extends ViewRecord
{
    protected static string $resource = DealResource::class;

    protected string $view = 'filament.admin.resources.deal-resource.pages.view-deal';

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Rediģēt'),
        ];
    }
}
