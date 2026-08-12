<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WpformEntryResource\Pages;

use App\Filament\Admin\Resources\WpformEntryResource;
use Filament\Actions;
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
                    dispatch(new \App\Jobs\SyncWpForms())->onQueue('sync');
                    \Filament\Notifications\Notification::make()
                        ->title('Sinhronizācija uzsākta')
                        ->success()
                        ->send();
                }),
        ];
    }
}
