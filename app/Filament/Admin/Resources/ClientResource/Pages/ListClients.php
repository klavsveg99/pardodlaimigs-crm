<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use App\Filament\Admin\Resources\Pages\Concerns\RefreshesTabBadges;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    use RefreshesTabBadges;

    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Jauns klients')->color('gray')];
    }

    public function getTabs(): array
    {
        // Aģentiem skaitītāji rāda tikai viņa paša klientus.
        $scoped = fn (Builder $query): Builder => $query->when(
            ! auth()->user()?->can('manage'),
            fn ($q) => $q->where('owner_user_id', auth()->id()),
        );
        $count = fn (Builder $query): int => $scoped($query)->count();
        $isActive = fn (Builder $query): Builder => $query->where(
            fn (Builder $q) => $q->whereNull('status')->orWhere('status', 'active'),
        );

        return [
            'active' => Tab::make('Aktīvie')
                ->modifyQueryUsing(fn (Builder $query): Builder => $isActive($query))
                ->badge($count($isActive(Client::query()))),
            'lead' => Tab::make('Līdi')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'lead'))
                ->badge($count(Client::query()->where('status', 'lead'))),
            'deleted' => Tab::make('Dzēstie')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->onlyTrashed())
                ->badge($count(Client::onlyTrashed())),
        ];
    }
}
