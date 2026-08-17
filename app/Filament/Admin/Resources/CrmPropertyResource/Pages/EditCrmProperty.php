<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Jobs\PushToWordPress;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCrmProperty extends EditRecord
{
    protected static string $resource = CrmPropertyResource::class;

    public function getTitle(): string
    {
        return 'Rediģēt īpašumu';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()->label('Skatīt'),
            Actions\Action::make('push_to_wp')
                ->label('Sūtīt uz WordPress')
                ->icon('heroicon-o-arrow-up-right')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sūtīt uz WordPress')
                ->modalDescription('Šis īpašums tiks nosūtīts uz WordPress. Visi dati tiks pārrakstīti.')
                ->modalSubmitActionLabel('Sūtīt')
                ->action(function () {
                    PushToWordPress::dispatch($this->record);
                    Notification::make()
                        ->title('Īpašums nosūtīts uz WordPress')
                        ->body("„{$this->record->title}“ tiks atjaunināts WordPress 5 minūšu laikā.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
