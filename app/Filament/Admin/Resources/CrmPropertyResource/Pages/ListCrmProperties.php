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
        // Aģentiem skaitītāji rāda tikai viņa paša īpašumus
        $count = fn (callable $conditions): int => $conditions(self::agentScoped(CrmProperty::query()))->count();

        return [
            'active' => Tab::make('Aktīvie')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotIn('status', ['draft', 'deleted', 'sold']))
                ->badge($count(fn ($q) => $q->whereNotIn('status', ['draft', 'deleted', 'sold']))),            'draft' => Tab::make('Melnraksti')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'draft'))
                ->badge($count(fn ($q) => $q->where('status', 'draft'))),
            'sold' => Tab::make('Pārdotie')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'sold'))
                ->badge($count(fn ($q) => $q->where('status', 'sold'))),
            'deleted' => Tab::make('Dzēstie')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'deleted'))
                ->badge($count(fn ($q) => $q->where('status', 'deleted'))),
        ];
    }

    private static function agentScoped(Builder $query): Builder
    {
        return $query->when(
            ! auth()->user()?->can('manage'),
            fn ($q) => $q->where('owner_user_id', auth()->id()),
        );
    }
}
