<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\PropertyCache;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

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
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn ($p) => [
                            $p->id => "{$p->title}".($p->city ? " · {$p->city}" : '').' · #'.$p->id,
                        ])
                        ->toArray();
                })
                ->getOptionLabelUsing(fn ($value): ?string => PropertyCache::find($value)?->title.' · #'.$value
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
                Actions\AttachAction::make()->label('Pievienot īpašumu'),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DetachAction::make()->label('Noņemt'),
            ]);
    }
}
