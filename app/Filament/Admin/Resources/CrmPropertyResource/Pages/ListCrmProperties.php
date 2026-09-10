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
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotIn('status', ['draft', 'deleted', 'sold']))
                ->badge(CrmProperty::query()->whereNotIn('status', ['draft', 'deleted', 'sold'])->count()),
            'draft' => Tab::make('Melnraksti')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge(CrmProperty::query()->where('status', 'draft')->count()),
            'sold' => Tab::make('Pārdotie')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sold'))
                ->badge(CrmProperty::query()->where('status', 'sold')->count()),
            'deleted' => Tab::make('Dzēstie')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'deleted'))
                ->badge(CrmProperty::query()->where('status', 'deleted')->count()),
        ];
    }
}
