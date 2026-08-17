<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\Client;
use App\Models\Deal;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
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
            Forms\Components\TextInput::make('title')->label('Nosaukums')->maxLength(255),
            Forms\Components\Select::make('stage')->label('Posms')
                ->options(Deal::STAGES)->default('jauns')->required(),
            Forms\Components\TextInput::make('value_eur')->label('Vērtība (€)')
                ->numeric()->prefix('€'),
            Forms\Components\Select::make('owner_user_id')->label('Īpašnieks')
                ->relationship('owner', 'name')->searchable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#'),
                Tables\Columns\TextColumn::make('title')->label('Nosaukums')->searchable()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('stage')
                    ->label('Posms')
                    ->badge()
                    ->sortable()
                    ->colors([
                        'info' => ['jauns', 'tirgosana'],
                        'warning' => 'pirma_tiksanas',
                        'primary' => 'noslegta_sadarbiba',
                        'gray' => 'foto_video',
                        'danger' => 'dokumentu_saskanosana',
                        'success' => 'pardots',
                    ])
                    ->formatStateUsing(fn ($state) => Deal::STAGES[$state] ?? $state),
                Tables\Columns\TextColumn::make('value_eur')
                    ->label('Vērtība')
                    ->money('EUR')->sortable(),
                Tables\Columns\TextColumn::make('closed_at')->label('Slēgts')->date('d.m.Y')->sortable(),
            ])
            ->headerActions([
                Actions\CreateAction::make()->label('Jauns darījums'),
                Actions\Action::make('new_client')
                    ->label('Jauns klients')
                    ->icon('heroicon-o-user-plus')
                    ->form([
                        Forms\Components\TextInput::make('name')->label('Vārds, uzvārds')->required(),
                        Forms\Components\TextInput::make('phone')->label('Tālrunis'),
                        Forms\Components\TextInput::make('email')->label('E-pasts')->email(),
                    ])
                    ->action(function (array $data): void {
                        $client = Client::create($data + ['source' => 'Cits']);
                        Notification::make()
                            ->title('Klients izveidots')
                            ->body("Klients #{$client->id} · {$client->name}")
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
