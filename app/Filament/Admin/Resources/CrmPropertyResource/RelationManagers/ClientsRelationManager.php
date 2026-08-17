<?php

namespace App\Filament\Admin\Resources\CrmPropertyResource\RelationManagers;

use App\Models\Client;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClientsRelationManager extends RelationManager
{
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
                ->options([
                    'seller' => 'Pārdevējs',
                    'buyer' => 'Pircējs',
                    'tenant' => 'Īrnieks',
                    'landlord' => 'Izīrētājs',
                    'interested' => 'Interesents',
                    'contacted' => 'Sazināts',
                ])
                ->required(),
            Forms\Components\Textarea::make('notes_md')->label('Piezīmes')->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('Klients')->weight('bold')->wrap(),
                Tables\Columns\TextColumn::make('phone')->label('Tālrunis')->wrap(),
                Tables\Columns\TextColumn::make('email')->label('E-pasts')->wrap(),
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
                    ->label('Mārketings')->boolean(),
            ])
            ->headerActions([
                Actions\AttachAction::make()
                    ->label('Piesaistīt klientu')
                    ->recordSelectSearchColumns(['name', 'email', 'phone', 'id'])
                    ->schema(function (Actions\AttachAction $action): array {
                        return [
                            $action->getRecordSelect(),
                            Forms\Components\Select::make('relation')
                                ->label('Saistība')
                                ->options([
                                    'seller' => 'Pārdevējs',
                                    'buyer' => 'Pircējs',
                                    'tenant' => 'Īrnieks',
                                    'landlord' => 'Izīrētājs',
                                    'interested' => 'Interesents',
                                    'contacted' => 'Sazināts',
                                ])
                                ->required(),
                            Forms\Components\Textarea::make('notes_md')->label('Piezīmes')->rows(3),
                        ];
                    })
                    ->validateRecordUsing(function (array $data): void {
                        $clientId = $data['recordId'] ?? null;
                        $relation = $data['relation'] ?? null;
                        $propertyId = $this->getOwnerRecord()->id;

                        if ($relation !== 'buyer') {
                            return;
                        }

                        $hasSeller = DB::table('client_crm_properties')
                            ->where('crm_property_id', $propertyId)
                            ->where('relation', 'seller')
                            ->exists();

                        if (! $hasSeller) {
                            throw ValidationException::withMessages([
                                'data.relation' => 'Īpašumam vispirms jābūt piesaistītam pārdevējam.',
                            ]);
                        }

                        if (! Client::find($clientId)?->marketing_consent) {
                            throw ValidationException::withMessages([
                                'data.relation' => 'Lai piesaistītu pircēju pārdotam īpašumam, klientam jābūt mārketinga piekrišanai.',
                            ]);
                        }
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DetachAction::make()->label('Noņemt'),
            ]);
    }
}
