<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Jobs\PushToWordPress;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListCrmProperties extends ListRecords
{
    protected static string $resource = CrmPropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Jauns īpašums'),
            Actions\Action::make('push_all')
                ->label('Sūtīt visus uz WordPress')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Sūtīt visus īpašumus uz WordPress')
                ->modalDescription('Tiks nosūtīti visi publicētie īpašumi. Tas var aizņemt laiku.')
                ->modalSubmitActionLabel('Sūtīt visus')
                ->action(function () {
                    $count = CrmProperty::where('status', '!=', 'draft')->count();
                    CrmProperty::where('status', '!=', 'draft')
                        ->each(fn (CrmProperty $p) => PushToWordPress::dispatch($p));

                    Notification::make()
                        ->title("Sūtīti {$count} īpašumi uz WordPress")
                        ->success()
                        ->send();
                }),
        ];
    }
}
