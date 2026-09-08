<?php

namespace App\Filament\Admin\Resources\CrmPropertyResource\RelationManagers;

use App\Filament\Admin\Resources\Pages\Concerns\PievienotKlientuRelationAction;
use App\Models\Client;
use App\Models\ClientCrmProperty;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Auth\Access\Response;

class ClientsRelationManager extends RelationManager
{
    use PievienotKlientuRelationAction;

    protected static string $relationship = 'clients';

    protected static ?string $title = 'Piesaistītie klienti';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('id')
                ->label('Klients')
                ->searchable()
                ->options(fn () => Client::query()->orderBy('name')->limit(20)->pluck('name', 'id')->all())
                ->getSearchResultsUsing(fn (string $search): array => Client::query()
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->limit(20)
                    ->pluck('name', 'id')
                    ->all())
                ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                ->required(),
            Forms\Components\Select::make('relation')
                ->label('Saistība')
                ->options(fn () => $this->getOwnerRecord()->status === 'sold'
                    ? ['seller' => 'Pārdevējs', 'buyer' => 'Pircējs', 'tenant' => 'Īrnieks', 'landlord' => 'Izīrētājs', 'interested' => 'Interesents', 'contacted' => 'Sazināts']
                    : collect(ClientCrmProperty::RELATIONS)->except('buyer')->all())
                ->required(),
            Forms\Components\Textarea::make('notes_md')->label('Piezīmes')->rows(3),
        ]);
    }

    /**
     * Allow attaching/detaching on the property VIEW page too — Filament
     * denies these actions on ViewRecord pages by default, which hid the
     * "Pievienot klientu" button in the Piesaistītie klienti section.
     */
    public function getDefaultActionAuthorizationResponse(Actions\Action $action): ?Response
    {
        if ($action instanceof Actions\AttachAction || $action instanceof Actions\DetachAction) {
            return null;
        }

        return parent::getDefaultActionAuthorizationResponse($action);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Klients')->sortable()->weight('bold')->wrap(),
                Tables\Columns\TextColumn::make('phone')->label('Tālrunis')->sortable()->wrap(),
                Tables\Columns\TextColumn::make('email')->label('E-pasts')->sortable()->wrap(),
                Tables\Columns\TextColumn::make('pivot.relation')
                    ->label('Saistība')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'buyer' => 'Pircējs',
                        'seller' => 'Pārdevējs',
                        'tenant' => 'Īrnieks',
                        'landlord' => 'Izīrētājs',
                        'interested' => 'Interesents',
                        'contacted' => 'Sazināts',
                        default => $state,
                    }),
                Tables\Columns\IconColumn::make('marketing_consent')
                    ->label('Mārketings')->boolean()->sortable(),
            ])
            ->headerActions([
                $this->getPievienotKlientuAction(),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->label('Rediģēt')->color('gray'),
                    Actions\DetachAction::make()->label('Noņemt')->color('gray'),
                ])->color('gray'),
            ]);
    }
}
