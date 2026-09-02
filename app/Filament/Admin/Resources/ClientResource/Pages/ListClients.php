<?php

namespace App\Filament\Admin\Resources\ClientResource\Pages;

use App\Filament\Admin\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()->label('Jauns klients')];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Visi'),
            'buyer' => Tab::make('Pircējs')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function (Builder $q) {
                    $q->where('client_type', 'buyer')
                      ->orWhereExists(function ($sq) {
                          $sq->selectRaw('1')->from('client_crm_properties')
                              ->whereColumn('client_crm_properties.client_id', 'clients.id')
                              ->where('client_crm_properties.relation', 'buyer');
                      });
                })),
            'seller' => Tab::make('Pārdevējs')
                ->modifyQueryUsing(fn (Builder $query) => $query->where(function (Builder $q) {
                    $q->where('client_type', 'seller')
                      ->orWhereExists(function ($sq) {
                          $sq->selectRaw('1')->from('client_crm_properties')
                              ->whereColumn('client_crm_properties.client_id', 'clients.id')
                              ->where('client_crm_properties.relation', 'seller');
                      });
                })),
        ];
    }
}
