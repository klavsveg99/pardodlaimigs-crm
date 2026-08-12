<?php

namespace App\Filament\Admin\Resources\PropertyResource\Pages;

use App\Filament\Admin\Resources\PropertyResource;
use App\Jobs\ReconcileAllProperties;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync')
                ->label('Sinhronizēt no WordPress')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    dispatch(new ReconcileAllProperties)->onQueue('sync');
                    Notification::make()
                        ->title('Īpašumu sinhronizācija uzsākta')
                        ->success()
                        ->send();
                }),
            Actions\Action::make('open_wp_admin')
                ->label('Atvērt īpašumu admin paneli')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(config('wp-bridge.wordpress.site_url').'/wp-admin/edit.php?post_type=property')
                ->openUrlInNewTab(),
        ];
    }
}
