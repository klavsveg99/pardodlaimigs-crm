<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

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
            Forms\Components\Select::make('assigned_user_id')->label('Aģents')
                ->relationship('assignedTo', 'name', modifyQueryUsing: fn (EloquentBuilder $query) => $query->assignable())->searchable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Uzdevums')->searchable()->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.tasks.edit', $record)),
                Tables\Columns\TextColumn::make('due_at')->label('Līdz')->dateTime('d.m.Y H:i')->sortable()->extraCellAttributes(['class' => 'pdc-nowrap'])
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null)
                    ->icon(fn ($record) => $record->isOverdue() ? 'heroicon-o-exclamation-triangle' : null)
                    ->iconColor('warning'),
                Tables\Columns\IconColumn::make('completed')
                    ->label('')
                    ->boolean()
                    ->getStateUsing(fn ($record) => (bool) $record->completed_at)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon(false)
                    ->trueColor('success')
                    ->tooltip(fn ($record) => $record->completed_at ? 'Izpildīts' : null)
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('completed_at', $direction)),
            ])
            ->headerActions([
                Actions\CreateAction::make()->label('Jauns uzdevums')->color('gray'),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\Action::make('complete')
                        ->label('Pabeigt')
                        ->icon('heroicon-o-check')
                        ->visible(fn ($record) => ! $record->completed_at)
                        ->action(fn ($record) => $record->update(['completed_at' => now()])),
                    Actions\Action::make('reopen')
                        ->label('Atzīmēt kā neizpildītu')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->visible(fn ($record) => (bool) $record->completed_at)
                        ->action(fn ($record) => $record->update(['completed_at' => null])),
                ])->color('gray'),
            ]);
    }
}
