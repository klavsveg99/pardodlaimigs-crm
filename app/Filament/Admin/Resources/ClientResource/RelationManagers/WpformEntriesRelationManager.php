<?php

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use App\Filament\Admin\Resources\WpformEntryResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WpformEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'wpformEntries';

    protected static ?string $title = 'Pieteikumi';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-inbox-stack';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pieteikumi')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Iesniegts')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('form_name')->label('Forma')->badge()->color('info'),
                Tables\Columns\TextColumn::make('email')->label('E-pasts')
                    ->getStateUsing(fn ($record) => $record->fieldValue('E-pasts'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('name')->label('Vārds')
                    ->getStateUsing(fn ($record) => $record->fieldValue('Jūsu vārds'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('phone')->label('Tālrunis')
                    ->getStateUsing(fn ($record) => $record->fieldValue('Telefona numurs'))
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->label('Statuss')
                    ->badge()
                    ->formatStateUsing(fn ($state) => WpformEntryResource::STATUSES[$state] ?? $state ?? '—')
                    ->color(fn ($state) => match ($state) {
                        'new' => 'info',
                        'review' => 'warning',
                        'replied' => 'success',
                        'spam' => 'danger',
                        'archived' => 'gray',
                        default => 'gray',
                    })
                    ->placeholder('—'),
            ])
            ->actions([
                Actions\ViewAction::make()->label('Skatīt')
                    ->url(fn ($record) => WpformEntryResource::getUrl('view', ['record' => $record])),
                Actions\Action::make('unlink')
                    ->label('Atsaistīt')
                    ->icon('heroicon-o-link-slash')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['client_id' => null]);
                        Notification::make()
                            ->title('Pieteikums atsaistīts no klienta')
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated([10, 25, 50]);
    }
}
