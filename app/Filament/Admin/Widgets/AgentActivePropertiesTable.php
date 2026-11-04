<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Models\CrmProperty;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class AgentActivePropertiesTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public ?int $agentId = null;

    /**
     * TableWidget piespiedž vienkāršu lapošanu; pārskatā gribam tādu pašu
     * lapošanu kā Īpašumu/Klientu tabulās (ar kopējo ierakstu skaitu).
     */
    protected function makeTable(): Table
    {
        return $this->makeBaseTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Objekti pārdošanā')
            ->extraAttributes(['class' => 'pdc-agent-report-table'])
            ->queryStringIdentifier('active')
            ->query(
                CrmProperty::query()
                    ->where('owner_user_id', $this->agentId ?? 0)
                    ->whereIn('status', ['published', 'reserved']),
            )
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Objekts')
                    ->searchable(['title', 'city', 'address'])
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (CrmProperty $record): ?string => collect([$record->city, $record->address])
                        ->filter()
                        ->implode(', ') ?: null)
                    ->url(fn (CrmProperty $record): string => CrmPropertyResource::getUrl('view', ['record' => $record])),

                Tables\Columns\TextColumn::make('category')
                    ->label('Tips')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price_eur')
                    ->label('Cena')
                    ->state(fn (CrmProperty $record): string => $record->price_display)
                    ->sortable()
                    ->alignEnd(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statuss')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): ?string => CrmProperty::STATUSES[$state] ?? $state)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Pievienots')
                    ->date('d.m.Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Tips')
                    ->options(CrmProperty::CATEGORIES)
                    ->multiple()
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statuss')
                    ->options([
                        'published' => CrmProperty::STATUSES['published'],
                        'reserved' => CrmProperty::STATUSES['reserved'],
                    ])
                    ->multiple()
                    ->preload(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Aktīvu objektu nav')
            ->emptyStateDescription('Kad aģentam būs aktīvs objekts, tas parādīsies šeit.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }
}
