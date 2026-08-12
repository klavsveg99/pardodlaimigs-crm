<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WpformEntryResource\Pages;

use App\Filament\Admin\Resources\WpformEntryResource;
use App\Jobs\SyncWpForms;
use App\Services\Wp\WpSource;
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
                    try {
                        $job = new SyncWpForms;
                        $job->handle(app(WpSource::class));
                        Notification::make()
                            ->title('Pieteikumi sinhronizēti')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Kļūda sinhronizācijā')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }
}
