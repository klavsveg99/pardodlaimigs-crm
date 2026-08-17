<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CrmPropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'crmProperties';

    protected static ?string $title = 'Piesaistītie īpašumi';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-building-office-2';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('relation')
                ->label('Saistība')
                ->options([
                    'seller' => 'Pārdevējs',
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
                Tables\Columns\TextColumn::make('title')->label('Īpašums')->sortable()->weight('bold')->wrap(),
                Tables\Columns\TextColumn::make('city')->label('Pilsēta')->sortable()->wrap(),
                Tables\Columns\TextColumn::make('kadastra_nr')->label('Kadastra nr.')->sortable()->placeholder('—')->wrap(),
                Tables\Columns\TextColumn::make('status')->label('Statuss')
                    ->badge()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => CrmProperty::STATUSES[$state] ?? $state),
                Tables\Columns\TextColumn::make('price_eur')->label('Cena')->money('EUR')->sortable(),
                Tables\Columns\TextColumn::make('pivot.relation')
                    ->label('Saistība')
                    ->badge()
                    ->colors([
                        'success' => 'buyer',
                        'danger' => 'seller',
                        'warning' => 'tenant',
                        'info' => 'landlord',
                        'gray' => ['interested', 'contacted'],
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'buyer' => 'Pircējs',
                        'seller' => 'Pārdevējs',
                        'tenant' => 'Īrnieks',
                        'landlord' => 'Izīrētājs',
                        'interested' => 'Interesents',
                        'contacted' => 'Sazināts',
                        default => $state,
                    }),
            ])
            ->headerActions([
                Actions\AttachAction::make()
                    ->label('Pievienot CRM īpašumu')
                    ->recordSelectSearchColumns(['title', 'city', 'kadastra_nr', 'id'])
                    ->schema(function (Actions\AttachAction $action): array {
                        return [
                            $action->getRecordSelect(),
                            Forms\Components\Select::make('relation')
                                ->label('Saistība')
                                ->options(function (Get $get): array {
                                    return CrmProperty::find($get('recordId'))?->status === 'sold'
                                        ? ['buyer' => 'Pircējs']
                                        : [
                                            'seller' => 'Pārdevējs',
                                            'tenant' => 'Īrnieks',
                                            'landlord' => 'Izīrētājs',
                                            'interested' => 'Interesents',
                                            'contacted' => 'Sazināts',
                                        ];
                                })
                                ->required(),
                            Forms\Components\Textarea::make('notes_md')->label('Piezīmes')->rows(3),
                        ];
                    })
                    ->validateRecordUsing(function (array $data): void {
                        $propertyId = $data['recordId'] ?? null;
                        $relation = $data['relation'] ?? null;
                        $clientId = $this->getOwnerRecord()->id;

                        if (! $propertyId || ! $relation) {
                            return;
                        }

                        $existingRelation = DB::table('client_crm_properties')
                            ->where('client_id', $clientId)
                            ->where('crm_property_id', $propertyId)
                            ->value('relation');

                        if ($existingRelation && $existingRelation !== $relation) {
                            throw ValidationException::withMessages([
                                'data.relation' => 'Klients nevar būt vienlaicīgi pircējs un pārdevējs tam pašam īpašumam.',
                            ]);
                        }

                        $property = CrmProperty::find($propertyId);
                        if ($relation === 'buyer') {
                            if (! $property || $property->status !== 'sold') {
                                throw ValidationException::withMessages([
                                    'data.relation' => 'Pircēju drīkst piesaistīt tikai pārdotam īpašumam.',
                                ]);
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

                            if (! $this->getOwnerRecord()->marketing_consent) {
                                throw ValidationException::withMessages([
                                    'data.relation' => 'Lai piesaistītu pircēju pārdotam īpašumam, klientam jābūt mārketinga piekrišanai.',
                                ]);
                            }
                        }
                    }),
            ])
            ->actions([
                Actions\DetachAction::make()->label('Noņemt'),
            ]);
    }
}
