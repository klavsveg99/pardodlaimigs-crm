<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Clusters\Mekletaji;
use App\Filament\Admin\Resources\PropertySearchResource\Pages;
use App\Jobs\RunPropertySearch;
use App\Models\PropertySearch;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PropertySearchResource extends Resource
{
    protected static ?string $model = PropertySearch::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Meklētāji';

    protected static ?string $cluster = Mekletaji::class;

    protected static ?string $modelLabel = 'Meklētājs';

    protected static ?string $pluralModelLabel = 'Meklētāji';

    protected static ?int $navigationSort = 40;

    public static function canAccess(): bool
    {
        return ! (auth()->user()?->isPhoto() ?? false)
            && ! (auth()->user()?->isStudent() ?? false);
    }

    public static function canCreate(): bool
    {
        if (auth()->user()?->can('manage')) {
            return true;
        }

        return PropertySearch::query()
            ->where('user_id', auth()->id())
            ->count() < PropertySearch::AGENT_LIMIT;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(
                ! auth()->user()?->can('manage'),
                fn (Builder $query) => $query->where('user_id', auth()->id()),
            );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            View::make('filament.forms.components.property-search-progress')
                ->columnSpanFull()
                ->visible(fn (string $operation): bool => $operation === 'edit')
                ->poll(fn (?PropertySearch $record = null): ?string => $record?->isProgressActive() ? '3s' : null),

            Section::make('Meklēšanas kritēriji')
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nosaukums')
                        ->required()
                        ->maxLength(120)
                        ->columnSpanFull(),
                    Forms\Components\CheckboxList::make('portals')
                        ->label('Portāls')
                        ->options(PropertySearch::PORTALS)
                        ->columns(2)
                        ->required(),
                    Forms\Components\Select::make('category')
                        ->label('Kategorija')
                        ->options(PropertySearch::CATEGORIES)
                        ->required()
                        ->native(false),
                    Forms\Components\TextInput::make('location')
                        ->label('Atrašanās vieta')
                        ->maxLength(120)
                        ->placeholder('Rīga, Jūrmala, Ogre...'),
                    Forms\Components\TextInput::make('keywords')
                        ->label('Atslēgvārdi')
                        ->maxLength(255)
                        ->placeholder('sadalāms, renovācija, daudzdzīvokļu'),
                    Forms\Components\TextInput::make('price_min')
                        ->label('Cena no (€)')
                        ->numeric()
                        ->minValue(0),
                    Forms\Components\TextInput::make('price_max')
                        ->label('Cena līdz (€)')
                        ->numeric()
                        ->minValue(0),
                    Forms\Components\TextInput::make('area_min')
                        ->label('Platība no (m²)')
                        ->numeric()
                        ->minValue(0),
                    Forms\Components\TextInput::make('area_max')
                        ->label('Platība līdz (m²)')
                        ->numeric()
                        ->minValue(0),
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktīvs')
                        ->default(true),
                    Forms\Components\Select::make('frequency')
                        ->label('Automātiskā pārbaude')
                        ->options(PropertySearch::FREQUENCIES)
                        ->default('daily')
                        ->required()
                        ->native(false),
                    Forms\Components\Select::make('user_id')
                        ->label('Aģents')
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload()
                        ->default(fn (): ?int => auth()->id())
                        ->visible(fn (): bool => auth()->user()?->can('manage') ?? false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'pdc-searches-table'])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nosaukums')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(22)
                    ->tooltip(fn (PropertySearch $record): ?string => mb_strlen((string) $record->name) > 22 ? $record->name : null)
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('portals')
                    ->label('Portāls')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PropertySearch::PORTALS[$state] ?? $state)
                    ->color('gray')
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategorija')
                    ->formatStateUsing(fn (string $state): string => PropertySearch::CATEGORIES[$state] ?? $state)
                    ->sortable()
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('location')
                    ->label('Vieta')
                    ->placeholder('-')
                    ->sortable()
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('price_range')
                    ->label('Cena')
                    ->state(fn (PropertySearch $record): ?string => self::range($record->price_min, $record->price_max, '€'))
                    ->placeholder('-')
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('area_range')
                    ->label('Platība')
                    ->state(fn (PropertySearch $record): ?string => self::range($record->area_min, $record->area_max, 'm²'))
                    ->placeholder('-')
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('last_run_at')
                    ->label('Pēdējā palaišana')
                    ->dateTime('d.m.Y H:i')
                    ->placeholder('Nav palaists')
                    ->sortable()
                    ->icon(fn (PropertySearch $record): ?string => filled($record->last_error) ? 'heroicon-o-exclamation-triangle' : null)
                    ->iconColor('danger')
                    ->tooltip(fn (PropertySearch $record): ?string => filled($record->last_error) ? $record->last_error : null)
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\ViewColumn::make('progress')
                    ->label('Progress')
                    ->view('filament.tables.columns.property-search-progress'),
                Tables\Columns\TextColumn::make('frequency')
                    ->label('Biežums')
                    ->formatStateUsing(fn (string $state): string => PropertySearch::FREQUENCIES[$state] ?? $state)
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Aģents')
                    ->visible(fn (): bool => auth()->user()?->can('manage') ?? false)
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('portals')
                    ->label('Portāls')
                    ->options(PropertySearch::PORTALS)
                    ->multiple()
                    ->query(function (Builder $query, array $data): Builder {
                        $values = array_filter((array) ($data['values'] ?? []));

                        if ($values === []) {
                            return $query;
                        }

                        return $query->where(function (Builder $query) use ($values): void {
                            foreach ($values as $value) {
                                $query->orWhereJsonContains('portals', $value);
                            }
                        });
                    }),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorija')
                    ->options(PropertySearch::CATEGORIES),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\Action::make('run')
                        ->label('Palaist tagad')
                        ->icon('heroicon-o-play')
                        ->color('gray')
                        ->action(function (PropertySearch $record): void {
                            $record->markProgressQueued(count(array_intersect(
                                (array) $record->portals,
                                array_keys(PropertySearch::PORTALS),
                            )));

                            RunPropertySearch::dispatch($record->id);
                            Notification::make()
                                ->title('Meklēšana palaista')
                                ->body('Rezultāti parādīsies pēc dažām minūtēm.')
                                ->success()
                                ->send();
                        }),
                    Actions\EditAction::make()->label('Rediģēt')->color('gray'),
                    Actions\DeleteAction::make()->label('Dzēst')->color('danger'),
                ])->color('gray'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->label('Dzēst izvēlētos'),
                ]),
            ])
            // Only keep polling while something is actually queued/running.
            ->poll(function (): ?string {
                $query = PropertySearch::query()->whereIn('progress_status', [
                    PropertySearch::PROGRESS_QUEUED,
                    PropertySearch::PROGRESS_RUNNING,
                ]);

                if (! auth()->user()?->can('manage')) {
                    $query->where('user_id', auth()->id());
                }

                return $query->exists() ? '3s' : null;
            })
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPropertySearches::route('/'),
            'create' => Pages\CreatePropertySearch::route('/create'),
            'edit' => Pages\EditPropertySearch::route('/{record}/edit'),
        ];
    }

    private static function range(?int $min, ?int $max, string $unit): ?string
    {
        if ($min === null && $max === null) {
            return null;
        }

        $format = fn (int $value): string => number_format($value, 0, ',', ' ');

        if ($min !== null && $max !== null) {
            return $format($min).' - '.$format($max).' '.$unit;
        }

        return $min !== null ? 'no '.$format($min).' '.$unit : 'līdz '.$format($max).' '.$unit;
    }
}
