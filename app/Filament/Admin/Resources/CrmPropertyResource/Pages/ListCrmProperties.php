<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CrmPropertyResource\Pages;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListCrmProperties extends ListRecords
{
    protected static string $resource = CrmPropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Jauns īpašums')->color('gray'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Aktīvie')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', '!=', 'deleted'))
                ->badge(CrmProperty::query()->where('status', '!=', 'deleted')->count()),
            'deleted' => Tab::make('Dzēstie')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'deleted'))
                ->badge(CrmProperty::query()->where('status', 'deleted')->count()),
        ];
    }
}
