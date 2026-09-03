<?php

namespace App\Filament\Admin\Resources\DealResource\Pages;

use App\Filament\Admin\Resources\DealResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeals extends ListRecords
{
    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resetFilters')
                ->label('Atiestatīt filtrus')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn () => $this->table->isFiltered())
                ->action(fn () => $this->resetTableFiltersForm()),
            Actions\CreateAction::make()->label('Jauns darījums'),
        ];
    }
}
