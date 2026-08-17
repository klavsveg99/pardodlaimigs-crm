<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\PropertyCache;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PropertiesRelationManager extends RelationManager
{
    protected static string $relationship = 'properties';

    protected static ?string $title = 'Piesaistītie īpašumi';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-home';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('id')
                ->label('Īpašums')
                ->searchable()
                ->getSearchResultsUsing(function (string $search) {
                    return PropertyCache::query()
                        ->where(function ($q) use ($search) {
                            $q->where('title', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%")
                                ->orWhere('kadastra_nr', 'like', "%{$search}%")
                                ->orWhere('id', '=', $search);
                        })
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn ($p) => [
                            $p->id => $p->selection_label.($p->city ? " · {$p->city}" : ''),
                        ])
                        ->toArray();
                })
                ->getOptionLabelUsing(fn ($value): ?string => PropertyCache::find($value)?->selection_label
                )
                ->required(),
            Forms\Components\Select::make('relation')
                ->label('Saistība')
                ->options([
                    'buyer' => 'Pircējs',
                    'seller' => 'Pārdevējs',
                    'tenant' => 'Īrnieks',
                    'landlord' => 'Izīrētājs',
                    'interested' => 'Interesents',
                    'contacted' => 'Sazināts',
                ])
                ->required(),
            Forms\Components\Textarea::make('notes_md')
                ->label('Piezīmes par šo interesi')
                ->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Īpašums')->weight('bold')->wrap()->limit(60),
                Tables\Columns\TextColumn::make('city')->label('Pilsēta'),
                Tables\Columns\TextColumn::make('kadastra_nr')->label('Kadastra nr.')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->label('Statuss')->badge(),
                Tables\Columns\TextColumn::make('price_display')
                    ->label('Cena')
                    ->money('EUR')
                    ->formatStateUsing(fn ($state) => $state ?: '—'),
                Tables\Columns\TextColumn::make('pivot.relation')
                    ->label('Saistība')
                    ->badge()
                    ->colors([
                        'success' => 'buyer',
                        'danger' => 'seller',
                        'warning' => 'tenant',
                        'info' => 'landlord',
                        'gray' => 'interested',
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
                Tables\Columns\TextColumn::make('wp_permalink')
                    ->label('WP saite')
                    ->formatStateUsing(fn ($state) => $state ? 'Atvērt →' : '—')
                    ->url(fn ($record) => $record->wp_permalink, shouldOpenInNewTab: true),
            ])
            ->headerActions([
                Actions\AttachAction::make()
                    ->label('Pievienot īpašumu')
                    ->validateRecordUsing(function (array $data, $record): void {
                        $propertyId = $data['id'] ?? $record?->id;
                        $relation = $data['relation'] ?? null;
                        $clientId = $this->getOwnerRecord()->id;

                        if (! $propertyId || ! $relation) {
                            return;
                        }

                        // Prevent same client being both buyer and seller on the same property.
                        $existingRelation = DB::table('client_properties')
                            ->where('client_id', $clientId)
                            ->where('property_id', $propertyId)
                            ->value('relation');

                        if ($existingRelation && $existingRelation !== $relation) {
                            throw ValidationException::withMessages([
                                'data.relation' => 'Klients nevar būt vienlaicīgi pircējs un pārdevējs tam pašam īpašumam.',
                            ]);
                        }

                        // Buyer requires an existing seller on the property.
                        if ($relation === 'buyer') {
                            $hasSeller = DB::table('client_properties')
                                ->where('property_id', $propertyId)
                                ->where('relation', 'seller')
                                ->exists();

                            if (! $hasSeller) {
                                throw ValidationException::withMessages([
                                    'data.relation' => 'Īpašumam vispirms jābūt piesaistītam pārdevējam.',
                                ]);
                            }

                            // Buyer on a sold property requires marketing consent.
                            $property = PropertyCache::find($propertyId);
                            if ($property && $property->status === 'publish' && str($property->category)->contains('Pārdots')) {
                                $client = $this->getOwnerRecord();
                                if (! $client->marketing_consent) {
                                    throw ValidationException::withMessages([
                                        'data.relation' => 'Lai piesaistītu pircēju pārdotam īpašumam, klientam jābūt mārketinga piekrišanai.',
                                    ]);
                                }
                            }
                        }
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DetachAction::make()->label('Noņemt'),
            ]);
    }
}
