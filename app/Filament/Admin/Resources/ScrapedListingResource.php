<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ScrapedListingResource\Pages;
use App\Jobs\RunPropertySearch;
use App\Models\PropertySearch;
use App\Models\ScrapedListing;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ScrapedListingResource extends Resource
{
    protected static ?string $model = ScrapedListing::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'Atrastie objekti';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $modelLabel = 'Atrastais objekts';

    protected static ?string $pluralModelLabel = 'Atrastie objekti';

    protected static ?int $navigationSort = 45;

    public static function canAccess(): bool
    {
        return ! (auth()->user()?->isPhoto() ?? false)
            && ! (auth()->user()?->isStudent() ?? false);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                ! auth()->user()?->can('manage'),
                fn (Builder $query) => $query->where('user_id', auth()->id()),
            );
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', 'new')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'gray';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('')
                    ->height(48)
                    ->width(64)
                    ->defaultImageUrl(asset('images/no-photo.svg')),
                Tables\Columns\TextColumn::make('title')
                    ->label('Nosaukums')
                    ->searchable()
                    ->wrap()
                    ->limit(80)
                    ->url(fn (ScrapedListing $record): string => $record->url)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('portal')
                    ->label('Portāls')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PropertySearch::PORTALS[$state] ?? $state)
                    ->color('gray'),
                Tables\Columns\TextColumn::make('price_eur')
                    ->label('Cena')
                    ->formatStateUsing(fn ($state): string => $state !== null
                        ? number_format((float) $state, 0, ',', ' ').' €'
                        : '-')
                    ->sortable()
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('area_m2')
                    ->label('Platība')
                    ->formatStateUsing(fn ($state): string => $state !== null ? $state.' m²' : '-')
                    ->sortable()
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('land_m2')
                    ->label('Zeme')
                    ->formatStateUsing(fn ($state): string => $state !== null ? $state.' m²' : '-')
                    ->toggleable()
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('location')
                    ->label('Vieta')
                    ->limit(30)
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('listed_at')
                    ->label('Publicēts')
                    ->date('d.m.Y')
                    ->placeholder('-')
                    ->sortable()
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('first_seen_at')
                    ->label('Atrasts')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statuss')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ScrapedListing::STATUSES[$state] ?? $state)
                    ->color('gray'),
                Tables\Columns\IconColumn::make('duplicate_of_id')
                    ->label('Dublikāts')
                    ->state(fn (ScrapedListing $record): bool => filled($record->duplicate_of_id))
                    ->boolean()
                    ->trueIcon('heroicon-o-document-duplicate')
                    ->trueColor('warning')
                    ->falseIcon('heroicon-o-minus')
                    ->falseColor('gray')
                    ->tooltip('Iespējams dublikāts citā portālā'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Aģents')
                    ->visible(fn (): bool => auth()->user()?->can('manage') ?? false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('portal')
                    ->label('Portāls')
                    ->options(PropertySearch::PORTALS),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statuss')
                    ->options(ScrapedListing::STATUSES),
                Tables\Filters\SelectFilter::make('property_search_id')
                    ->label('Meklētājs')
                    ->relationship('search', 'name')
                    ->visible(fn (): bool => auth()->user()?->can('manage') ?? false),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\Action::make('open')
                        ->label('Atvērt sludinājumu')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('gray')
                        ->url(fn (ScrapedListing $record): string => $record->url)
                        ->openUrlInNewTab(),
                    Actions\Action::make('seen')
                        ->label('Atzīmēt apskatītu')
                        ->icon('heroicon-o-check')
                        ->color('gray')
                        ->visible(fn (ScrapedListing $record): bool => $record->status === 'new')
                        ->action(fn (ScrapedListing $record) => $record->update(['status' => 'seen'])),
                    Actions\Action::make('dismiss')
                        ->label('Atteikties')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->visible(fn (ScrapedListing $record): bool => $record->status !== 'dismissed')
                        ->action(fn (ScrapedListing $record) => $record->update(['status' => 'dismissed'])),
                    Actions\Action::make('restore')
                        ->label('Atjaunot')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->visible(fn (ScrapedListing $record): bool => $record->status === 'dismissed')
                        ->action(fn (ScrapedListing $record) => $record->update(['status' => 'new'])),
                ])->color('gray'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('dismiss_selected')
                        ->label('Atteikties no izvēlētajiem')
                        ->icon('heroicon-o-x-mark')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['status' => 'dismissed']))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('first_seen_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScrapedListings::route('/'),
        ];
    }
}
