<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Filament\Admin\Resources\Pages\Concerns\AttachSellerAction;
use App\Filament\Admin\Resources\Pages\Concerns\SyncsAttachments;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrmProperty extends EditRecord
{
    use AttachSellerAction;
    use SyncsAttachments;

    protected static string $resource = CrmPropertyResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt īpašumu';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Saglabāt')
                ->icon('heroicon-o-check')
                ->color('primary')
                ->keyBindings(['mod+s'])
                ->action(function () {
                    $this->save();
                }),
            $this->getAttachSellerAction(),
            $this->getAttachBuyerAction(),
            Actions\Action::make('open_site')
                ->label('Skatīt')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->record->public_url)
                ->openUrlInNewTab(),
        ];
    }
}
