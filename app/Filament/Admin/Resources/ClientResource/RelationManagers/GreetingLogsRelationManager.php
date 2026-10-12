<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ClientResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Klientam automātiski nosūtīto apsveikumu vēsture (tikai skatāms).
 * Ierakstus veido komanda pdc:send-greetings, saglabājot tos email_logs.
 */
class GreetingLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'greetingLogs';

    protected static ?string $title = 'Apsveikumi';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-gift';

    public function canCreate(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Apsveikumi')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Nosūtīts')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('context')->label('Veids')->badge()
                    ->formatStateUsing(fn ($state): string => str_starts_with((string) $state, 'greeting_birthday')
                        ? 'Dzimšanas diena'
                        : 'Svētki'),
                Tables\Columns\TextColumn::make('subject')->label('Temats')->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Apsveikumi vēl nav nosūtīti')
            ->emptyStateIcon('heroicon-o-gift');
    }
}
