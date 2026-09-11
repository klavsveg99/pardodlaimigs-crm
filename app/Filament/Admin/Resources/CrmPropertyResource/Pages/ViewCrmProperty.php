<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Filament\Admin\Resources\Pages\Concerns\PievienotKlientuRelationAction;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCrmProperty extends ViewRecord
{
    use PievienotKlientuRelationAction;

    protected static string $resource = CrmPropertyResource::class;

    protected string $view = 'filament.admin.resources.crm-property-resource.pages.view-crm-property';

    protected function getHeaderActions(): array
    {
        return [
            $this->getPievienotKlientuAction(),
            Actions\EditAction::make()->label('Rediģēt'),
            Actions\Action::make('open_site')
                ->label('Atvērt mājaslapā')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn () => $this->record->public_url)
                ->openUrlInNewTab(),
            Actions\Action::make('restore_property')
                ->label('Atjaunot')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->visible(fn (): bool => $this->record?->status === 'deleted')
                ->requiresConfirmation()
                ->modalHeading('Atjaunot īpašumu?')
                ->modalDescription('Īpašums atgriezīsies kā melnraksts un būs redzams aktīvo īpašumu sarakstā.')
                ->modalSubmitActionLabel('Atjaunot')
                ->action(function (): void {
                    $this->record->update(['status' => 'draft']);
                    Notification::make()
                        ->title('Īpašums atjaunots')
                        ->success()
                        ->send();
                }),
        ];
    }
}
