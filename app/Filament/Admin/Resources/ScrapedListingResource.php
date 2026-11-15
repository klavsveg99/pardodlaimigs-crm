<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Clusters\Mekletaji;
use App\Filament\Admin\Resources\ScrapedListingResource\Pages;
use App\Models\PropertySearch;
use App\Models\ScrapedListing;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ScrapedListingResource extends Resource
{
    protected static ?string $model = ScrapedListing::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationLabel = 'Atrastie objekti';

    protected static ?string $cluster = Mekletaji::class;

    protected static ?string $modelLabel = 'Atrastais objekts';

    protected static ?string $pluralModelLabel = 'Atrastie objekti';

    protected static ?int $navigationSort = 41;

    public static function canAccess(): bool
    {
        return ! (auth()->user()?->isPhoto() ?? false)
            && ! (auth()->user()?->isStudent() ?? false);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            // A cross-portal duplicate is stored only to keep the dedupe
            // trail; the inbox shows each property once.
            ->whereNull('duplicate_of_id')
            ->when(
                ! auth()->user()?->can('manage'),
                fn (Builder $query) => $query->where('user_id', auth()->id()),
            );
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'pdc-found-table pdc-crm-table'])
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('')
                    ->height(40)
                    ->width(60)
                    ->defaultImageUrl(asset('images/no-photo.svg'))
                    ->extraImgAttributes(['style' => 'border-radius: 4px; width: 60px; height: 40px; object-fit: cover;']),
                Tables\Columns\TextColumn::make('title')
                    ->label('Nosaukums')
                    ->searchable()
                    ->weight('bold')
                    ->limit(80)
                    ->tooltip(fn (ScrapedListing $record): string => (string) $record->title)
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
                Tables\Columns\TextColumn::make('location')
                    ->label('Vieta')
                    ->limit(30)
                    ->placeholder('-'),
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
                    Actions\Action::make('create_property')
                        ->label('Pievienot CRM īpašumu')
                        ->icon('heroicon-o-plus')
                        ->color('gray')
                        ->url(fn (ScrapedListing $record): string => CrmPropertyResource::getUrl('create', [
                            'scraped_listing' => $record->id,
                        ])),
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
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Vai tiešām dzēst šo atrasto objektu?')
                        ->modalDescription('Ieraksts tiks neatgriezeniski izdzēsts un to vairs nevarēs atjaunot.')
                        ->modalSubmitActionLabel('Dzēst neatgriezeniski')
                        ->action(function (ScrapedListing $record): void {
                            $record->delete();

                            Notification::make()
                                ->title('Atrastais objekts izdzēsts')
                                ->success()
                                ->send();
                        }),
                ])->color('gray'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('open_selected')
                        ->label('Atvērt sludinājumus')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('gray')
                        ->modalHeading('Atvērt sludinājumus')
                        ->modalContent(fn (Collection $records) => view('filament.scraped-listing-links', ['records' => $records]))
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Aizvērt'),
                    Actions\BulkAction::make('seen_selected')
                        ->label('Atzīmēt apskatītus')
                        ->icon('heroicon-o-check')
                        ->color('gray')
                        ->action(function (Collection $records): void {
                            $records->each->update(['status' => 'seen']);

                            Notification::make()
                                ->title($records->count().' atrastie objekti atzīmēti kā apskatīti')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Actions\BulkAction::make('dismiss_selected')
                        ->label('Atteikties')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Vai tiešām dzēst izvēlētos atrastos objektus?')
                        ->modalDescription('Ieraksti tiks neatgriezeniski izdzēsti un tos vairs nevarēs atjaunot.')
                        ->modalSubmitActionLabel('Dzēst neatgriezeniski')
                        ->action(function (Collection $records): void {
                            $deleted = 0;

                            foreach ($records as $record) {
                                $record->delete();
                                $deleted++;
                            }

                            Notification::make()
                                ->title($deleted.' atrastie objekti izdzēsti')
                                ->success()
                                ->send();
                        })
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
