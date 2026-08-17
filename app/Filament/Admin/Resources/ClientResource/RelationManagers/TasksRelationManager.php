<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Uzdevumi';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-clipboard-document-check';

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('title')->label('Uzdevums')->required(),
            Forms\Components\Textarea::make('body')->label('Apraksts')->rows(3),
            Forms\Components\DateTimePicker::make('due_at')->label('Līdz')->native(false),
            Forms\Components\Select::make('assigned_user_id')->label('Kam')
                ->relationship('assignedTo', 'name')->searchable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Uzdevums')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('due_at')->label('Līdz')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\IconColumn::make('completed_at')->label('Pabeigts')->boolean()->sortable(),
            ])
            ->headerActions([
                Actions\CreateAction::make()->label('Jauns uzdevums'),
            ])
            ->actions([
                Actions\Action::make('complete')
                    ->label('Pabeigt')
                    ->icon('heroicon-o-check')
                    ->visible(fn ($record) => ! $record->completed_at)
                    ->action(fn ($record) => $record->update(['completed_at' => now()])),
            ]);
    }
}
