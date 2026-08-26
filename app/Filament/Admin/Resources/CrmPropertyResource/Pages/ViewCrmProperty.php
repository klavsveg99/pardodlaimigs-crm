<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Filament\Admin\Resources\Pages\Concerns\AttachSellerAction;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCrmProperty extends ViewRecord
{
    use AttachSellerAction;

    protected static string $resource = CrmPropertyResource::class;

    protected string $view = 'filament.admin.resources.crm-property-resource.pages.view-crm-property';

    protected function getHeaderActions(): array
    {
        return [
            $this->getAttachSellerAction(),
            $this->getAttachBuyerAction(),
            Actions\EditAction::make()->label('Rediģēt'),
            Actions\Action::make('open_site')
                ->label('Atvērt vietnē')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->record->public_url)
                ->openUrlInNewTab(),
        ];
    }
}
