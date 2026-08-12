<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WpformEntryResource\Pages;

use App\Filament\Admin\Resources\WpformEntryResource;
use App\Jobs\SyncWpForms;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListWpformEntries extends ListRecords
{
    protected static string $resource = WpformEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label('Sinhronizēt no WordPress')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    dispatch(new SyncWpForms)->onQueue('sync');
                    Notification::make()
                        ->title('Sinhronizācija uzsākta')
                        ->success()
                        ->send();
                }),
        ];
    }
}
