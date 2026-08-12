<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PropertyResource\Pages;
use App\Models\PropertyCache;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Read-only mirror of WP's Essential Real Estate properties cache.
 * Editing rows is disabled; redirect to the WP admin for changes.
 */
class PropertyResource extends Resource
{
    protected static ?string $model = PropertyCache::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Īpašumi';

    protected static string|UnitEnum|null $navigationGroup = 'Avots';

    protected static ?string $modelLabel = 'Īpašums';

    protected static ?string $pluralModelLabel = 'Īpašumi';

    protected static ?int $navigationSort = 40;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->orderByDesc('wp_updated_at')->orderByDesc('cached_at'))
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label('')
                    ->size(50)
                    ->defaultImageUrl(asset('images/no-photo.svg')),
                Tables\Columns\TextColumn::make('title')->label('Nosaukums')->searchable()->wrap()->limit(50)->weight('bold'),
                Tables\Columns\TextColumn::make('category')->label('Kategorija')->badge()->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statuss')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'publish' => 'Pārdošanā',
                        'expired' => 'Pārdots',
                        'hidden' => 'Dzēsts',
                        default => $state,
                    })
                    ->color(fn ($state) => match ($state) {
                        'publish' => 'warning',
                        'expired' => 'success',
                        'hidden' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('address')->label('Adrese')->searchable()->wrap()->limit(40)->placeholder('—'),
                Tables\Columns\TextColumn::make('size_m2')
                    ->label('m²')
                    ->formatStateUsing(function ($record) {
                        if ($record->size_m2 === null || $record->size_m2 === '') {
                            return '—';
                        }
                        $v = (float) $record->size_m2;

                        return ($v == (int) $v) ? number_format((int) $v, 0, '.', ' ') : number_format($v, 2, ',', ' ');
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('land_m2')
                    ->label('Zeme')
                    ->formatStateUsing(function ($record) {
                        if ($record->land_m2 === null || $record->land_m2 === '') {
                            return '—';
                        }
                        $v = (float) $record->land_m2;
                        $ha = $v / 10000;
                        if ($ha >= 1) {
                            $formatted = number_format($ha, 2, ',', ' ');
                            $formatted = rtrim(rtrim($formatted, '0'), ',');

                            return $formatted.' ha';
                        }

                        return number_format((int) round($v), 0, ',', ' ').' m²';
                    })
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('price_cents')
                    ->label('Cena')
                    ->formatStateUsing(fn ($record) => $record->price_display),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorija')
                    ->options(fn () => PropertyCache::whereNotNull('category')->distinct()->orderBy('category')->pluck('category', 'category')->all())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statuss')
                    ->options([
                        'publish' => 'Pārdošanā',
                        'expired' => 'Pārdots',
                        'hidden' => 'Dzēsts',
                    ]),
            ])
            ->recordUrl(fn ($record) => $record->wp_permalink
                ? 'https://pardodlaimigs.lv/wp-admin/post.php?post='.$record->id.'&action=edit'
                : null)
            ->openRecordUrlInNewTab()
            ->bulkActions([])
            ->paginated([25, 50, 100]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
        ];
    }
}
