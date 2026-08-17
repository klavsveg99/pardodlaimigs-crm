<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Models\Client;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ViewingsRelationManager extends RelationManager
{
    protected static string $relationship = 'viewings';

    protected static ?string $title = 'Apskates';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-calendar';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('property_id')->label('Īpašums')
                ->relationship('property', 'title')->searchable()->required(),
            Forms\Components\DateTimePicker::make('scheduled_at')->label('Datums/laiks')->native(false)->required(),
            Forms\Components\TextInput::make('duration_min')->label('Ilgums (min)')->numeric()->default(30),
            Forms\Components\Select::make('status')->label('Statuss')->options([
                'scheduled' => 'Ieplānota',
                'done' => 'Notikusi',
                'cancelled' => 'Atcelta',
                'no_show' => 'Klients neatnāca',
            ])->default('scheduled'),
            Forms\Components\Textarea::make('notes_md')->label('Piezīmes')->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_at')->label('Kad')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('property.title')->label('Īpašums')->limit(40)->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Statuss')->badge()->sortable(),
                Tables\Columns\TextColumn::make('agent.name')->label('Aģents')->sortable(),
            ])
            ->headerActions([
                Actions\CreateAction::make()->label('Jauna apskate'),
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
            ])
            ->defaultSort('scheduled_at', 'desc');
    }
}
