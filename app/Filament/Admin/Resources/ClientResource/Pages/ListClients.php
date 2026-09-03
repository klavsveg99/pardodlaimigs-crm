<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resetFilters')
                ->label('Atiestatīt filtrus')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn () => $this->table->isFiltered())
                ->action(fn () => $this->resetTableFiltersForm()),
            Actions\CreateAction::make()->label('Jauns klients'),
        ];
    }
}
