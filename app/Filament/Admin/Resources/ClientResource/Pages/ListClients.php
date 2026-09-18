<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Jauns klients')->color('gray')];
    }

    public function getTabs(): array
    {
        // Aģentiem skaitītāji rāda tikai viņa paša klientus.
        $count = fn (Builder $query): int => $query->when(
            ! auth()->user()?->can('manage'),
            fn ($q) => $q->where('owner_user_id', auth()->id()),
        )->count();

        return [
            'active' => Tab::make('Aktīvie')
                ->badge($count(Client::query())),
            'deleted' => Tab::make('Dzēstie')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->onlyTrashed())
                ->badge($count(Client::onlyTrashed())),
        ];
    }
}
