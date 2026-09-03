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
            Actions\Action::make('resetFilters')
                ->label('Atiestatīt filtrus')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn () => $this->table->isFiltered())
                ->action(fn () => $this->resetTableFiltersForm()),
        ];
    }
}
