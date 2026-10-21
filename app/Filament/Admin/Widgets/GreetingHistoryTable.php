<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\EmailLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Visu nosūtīto apsveikumu vēsture (visi klienti). Rādīta "Iestatījumi"
 * lapas apakšā. Meklējama un ar lapošanu.
 */
class GreetingHistoryTable extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 10;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                EmailLog::query()
                    ->greetings()
                    ->with('client')
                    ->orderByDesc('created_at')
            )
            ->heading('Nosūtīto apsveikumu vēsture')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Nosūtīts')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Klients')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('context')
                    ->label('Veids')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => EmailLog::greetingTypeLabel($state)),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Temats')
                    ->searchable()
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
