<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\CrmPropertyResource;
use App\Models\CrmProperty;
use Filament\Tables;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;

class AgentSoldPropertiesTable extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    public ?int $agentId = null;

    public int $year = 0;

    /**
     * TableWidget piespiedž vienkāršu lapošanu; pārskatā gribam tādu pašu
     * lapošanu kā Īpašumu/Klientu tabulās (ar kopējo ierakstu skaitu).
     */
    protected function makeTable(): Table
    {
        return $this->makeBaseTable();
    }

    public function mount(): void
    {
        $this->year = $this->year ?: (int) now()->year;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Pārdotie objekti un saņemtā komisija')
            ->query(
                CrmProperty::query()
                    ->where('owner_user_id', $this->agentId ?? 0)
                    ->where('status', 'sold')
                    ->where(function (Builder $query): void {
                        $query->whereBetween('sold_at', [
                            Carbon::create($this->year, 1, 1)->startOfDay(),
                            Carbon::create($this->year, 12, 31)->endOfDay(),
                        ])->orWhereNull('sold_at');
                    }),
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

                Tables\Columns\TextColumn::make('sold_at')
                    ->label('Darījuma datums')
                    ->date('d.m.Y')
                    ->sortable()
                    ->placeholder('Nav ievadīts')
                    ->color(fn ($state): ?string => $state === null ? 'warning' : null),

                Tables\Columns\TextColumn::make('final_price_eur')
                    ->label('Pārdošanas cena')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->placeholder('Nav ievadīts')
                    ->color(fn ($state): ?string => $state === null ? 'warning' : null)
                    ->summarize(
                        Sum::make()
                            ->label('Kopā')
                            ->money('EUR')
                            ->query(fn (QueryBuilder $query): QueryBuilder => $query->whereNotNull('sold_at')),
                    ),

                Tables\Columns\TextColumn::make('commission_eur')
                    ->label('Komisija kopā')
                    ->money('EUR')
                    ->sortable()
                    ->alignEnd()
                    ->placeholder('Nav ievadīts')
                    ->color(fn ($state): ?string => $state === null ? 'warning' : null)
                    ->summarize(
                        Sum::make()
                            ->label('Kopā')
                            ->money('EUR')
                            ->query(fn (QueryBuilder $query): QueryBuilder => $query->whereNotNull('sold_at')),
                    ),

                Tables\Columns\TextColumn::make('commission_percent')
                    ->label('Aģenta %')
                    ->state(fn (CrmProperty $record): ?float => $record->commission_percent)
                    ->formatStateUsing(fn ($state): ?string => $state === null
                        ? null
                        : number_format((float) $state, 2, ',', ' ').' %')
                    ->alignEnd()
                    ->placeholder('Nav ievadīts')
                    ->color(fn ($state): ?string => $state === null ? 'warning' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('lead_source')
                    ->label('Pieteikuma avots')
                    ->options(CrmProperty::LEAD_SOURCES),
            ])
            ->defaultSort('sold_at', 'desc')
            ->summaries(pageCondition: false, allTableCondition: true)
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->emptyStateHeading('Pārdoto objektu nav')
            ->emptyStateDescription('Kad aģentam būs pārdots objekts, tas parādīsies šeit.')
            ->emptyStateIcon('heroicon-o-building-office-2');
    }
}
