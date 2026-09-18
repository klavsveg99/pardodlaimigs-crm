<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\IzpilditajsResource\Pages;

use App\Filament\Admin\Resources\IzpilditajsResource;
use App\Filament\Admin\Resources\Pages\Concerns\RefreshesTabBadges;
use App\Models\Izpilditajs;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListIzpilditajs extends ListRecords
{
    use RefreshesTabBadges;

    protected static string $resource = IzpilditajsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Jauns izpildītājs')->color('gray'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Aktīvie')
                ->badge(Izpilditajs::query()->count()),
            'deleted' => Tab::make('Dzēstie')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->onlyTrashed())
                ->badge(Izpilditajs::onlyTrashed()->count()),
        ];
    }
}
