<?php

namespace App\Filament\Admin\Resources\ViewingResource\Pages;

use App\Filament\Admin\Resources\CalendarResource\Pages\CalendarPage;
use App\Filament\Admin\Resources\ViewingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListViewings extends ListRecords
{
    protected static string $resource = ViewingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resetFilters')
                ->label('Atiestatīt filtrus')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn () => $this->table->isFiltered())
                ->action(fn () => $this->resetTableFiltersForm()),
            Actions\Action::make('calendar')
                ->label('Kalendārs')
                ->icon('heroicon-o-calendar-days')
                ->url(CalendarPage::getUrl()),
            Actions\CreateAction::make()->label('Jauna apskate'),
        ];
    }
}
