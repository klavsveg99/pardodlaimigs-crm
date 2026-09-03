<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCrmProperties extends ListRecords
{
    protected static string $resource = CrmPropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('resetFilters')
                ->label('Atiestatīt filtrus')
                ->icon('heroicon-o-x-mark')
                ->color('danger')
                ->visible(fn () => $this->table->isFiltered())
                ->action(fn () => $this->resetTableFiltersForm()),
            Actions\CreateAction::make()->label('Jauns īpašums'),
        ];
    }
}
