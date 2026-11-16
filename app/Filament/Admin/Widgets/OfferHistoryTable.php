<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\EmailLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Visu nosūtīto piedāvājumu vēsture (atrastie Meklētāju objekti un īpašumu
 * interesenti). Rādīta "Iestatījumi" lapas sadaļā "Piedāvājumu nosūtīšana".
 */
class OfferHistoryTable extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 20;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                EmailLog::query()
                    ->offers()
                    ->with(['client', 'property', 'scrapedListing'])
                    ->orderByDesc('created_at')
            )
            ->heading('Nosūtīto piedāvājumu vēsture')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Nosūtīts')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('context')
                    ->label('Veids')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => EmailLog::offerTypeLabel($state)),
                Tables\Columns\TextColumn::make('target')
                    ->label('Objekts')
                    ->state(fn (EmailLog $record): string => $record->scraped_listing_id
                        ? (string) ($record->scrapedListing?->title ?: 'Atrastais objekts #'.$record->scraped_listing_id)
                        : (string) ($record->property?->title ?: 'Īpašums #'.$record->property_id))
                    ->wrap(),
                Tables\Columns\TextColumn::make('to_email')
                    ->label('Saņēmējs')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Temats')
                    ->searchable()
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}
