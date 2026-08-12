<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\Deal;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class DealsRelationManager extends RelationManager
{
    protected static string $relationship = 'deals';

    protected static ?string $title = 'Darījumi';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-currency-euro';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('stage')->label('Posms')
                ->options(Deal::STAGES)->required(),
            Forms\Components\TextInput::make('value_cents')->label('Vērtība (centos)')
                ->numeric(),
            Forms\Components\DatePicker::make('expected_close_date')->label('Plānotais datums'),
            Forms\Components\Select::make('owner_user_id')->label('Īpašnieks')
                ->relationship('owner', 'name')->searchable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#'),
                Tables\Columns\TextColumn::make('stage')
                    ->label('Posms')
                    ->badge()
                    ->colors([
                        'gray' => 'lead',
                        'info' => 'viewing_scheduled',
                        'warning' => 'offer',
                        'primary' => 'reserved',
                        'success' => 'closed_won',
                        'danger' => 'closed_lost',
                    ])
                    ->formatStateUsing(fn ($state) => Deal::STAGES[$state] ?? $state),
                Tables\Columns\TextColumn::make('value_cents')
                    ->label('Vērtība')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 0, '.', ' ').' €' : '—'),
                Tables\Columns\TextColumn::make('expected_close_date')->label('Plānots')->date('d.m.Y'),
                Tables\Columns\TextColumn::make('closed_at')->label('Slēgts')->date('d.m.Y'),
            ])
            ->headerActions([
                Actions\CreateAction::make()->label('Jauns darījums'),
            ]);
    }
}
