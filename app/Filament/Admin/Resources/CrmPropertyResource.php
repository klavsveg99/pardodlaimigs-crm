<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CrmPropertyResource\Pages;
use App\Models\ClientCrmProperty;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Query\Builder;
use UnitEnum;

class CrmPropertyResource extends Resource
{
    protected static ?string $model = CrmProperty::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Īpašumi';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $modelLabel = 'Īpašums';

    protected static ?string $pluralModelLabel = 'Īpašumi';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Grid::make(2)->schema([
                Section::make('Pamatdati')->columns(2)->schema([
                    Forms\Components\TextInput::make('title')
                        ->label('Nosaukums')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('category')
                        ->label('Kategorija')
                        ->options(CrmProperty::CATEGORIES)
                        ->searchable(),

                    Forms\Components\Select::make('status')
                        ->label('Statuss')
                        ->options(CrmProperty::STATUSES)
                        ->default('draft')
                        ->required()
                        ->live(),

                    Grid::make(3)
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => $get('status') === 'sold')
                        ->schema([
                            Forms\Components\TextInput::make('final_price_eur')
                                ->label('Gala cena (€)')
                                ->numeric()
                                ->prefix('€')
                                ->placeholder('Ievadi gala cenu')
                                ->live(onBlur: false),
                            Forms\Components\TextInput::make('commission_eur')
                                ->label('Komisijas summa (€)')
                                ->numeric()
                                ->prefix('€')
                                ->placeholder('Ievadi komisiju')
                                ->live(onBlur: false),
                            Forms\Components\Placeholder::make('commission_percent_display')
                                ->label('Komisija %')
                                ->content(function (Get $get): string {
                                    $final = (float) ($get('final_price_eur') ?? 0);
                                    $comm = (float) ($get('commission_eur') ?? 0);
                                    if ($final <= 0 || $comm <= 0) {
                                        return '—';
                                    }
                                    $percent = $comm / $final * 100;

                                    return number_format($percent, 2, ',', ' ') . ' %';
                                }),
                        ]),

                    Forms\Components\TextInput::make('price_eur')
                        ->label('Cena (€)')
                        ->numeric()
                        ->prefix('€'),

                    Forms\Components\Select::make('owner_user_id')
                        ->label('Atbildīgais')
                        ->relationship('owner', 'name')
                        ->searchable()
                        ->preload(),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ])->columnSpan(1),

                Section::make('Īpašuma dati')->columns(2)->schema([
                    Forms\Components\TextInput::make('beds')->label('Istabas')->numeric(),
                    Forms\Components\TextInput::make('baths')->label('Vannas istabas')->numeric(),
                    Forms\Components\TextInput::make('size_m2')->label('Platība (m²)')->numeric(),
                    Forms\Components\TextInput::make('land_m2')->label('Zemes platība (m²)')->numeric(),
Forms\Components\TextInput::make('kadastra_nr')
                         ->label('Kadastra nr.')
                         ->required(fn (Get $get): bool => !filled($get('id')))
                         ->maxLength(32)
                         ->columnSpanFull()
                         ->helperText(fn (Get $get): ?string => !filled($get('kadastra_nr')) ? 'Lauku aizpildīšana ir obligāta' : null),
                ])->columnSpan(1),
            ])->columnSpanFull(),

            Section::make('Atrašanās vieta')->columns(2)->schema([
                Forms\Components\TextInput::make('city')
                    ->label('Pilsēta')
                    ->maxLength(128)
                    ->nullable(),

                Forms\Components\TextInput::make('address')
                    ->label('Adrese')
                    ->maxLength(255)
                    ->nullable(),

                Forms\Components\Hidden::make('lat'),
                Forms\Components\Hidden::make('lng'),
                View::make('filament.forms.components.google-maps-picker')
                    ->columnSpanFull()
                    ->viewData([
                        'latField' => 'lat',
                        'lngField' => 'lng',
                        'cityField' => 'city',
                        'addressField' => 'address',
                    ]),
            ])->columnSpanFull(),

            Section::make('Apraksts')->columnSpanFull()->schema([
                Forms\Components\RichEditor::make('description')
                    ->hiddenLabel()
                    ->extraInputAttributes(['style' => 'min-height: 280px'])
                    ->columnSpanFull(),
            ])->columnSpanFull(),

            Section::make('Pielikumi')->columnSpanFull()->schema([
                \App\Filament\Forms\Components\AttachmentsGrid::make('attachments')
                    ->label('Fotogrāfijas un plānojumi')
                    ->reorderable()
                    ->deletable()
                    ->multiselect()
                    ->columnSpanFull(),

                Forms\Components\Hidden::make('attachment_original_names')
                    ->default([]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order', 'asc')
            ->columns([
                Tables\Columns\ImageColumn::make('featured_thumb')
                    ->label('')
                    ->getStateUsing(function (CrmProperty $record) {
                        $first = $record->attachments()->orderBy('sort_order')->first();
                        if ($first) {
                            return $first->url;
                        }
                        $urls = $record->image_urls ?? [];
                        return is_array($urls) && !empty($urls[0]) ? $urls[0] : null;
                    })
                    ->height(40)
                    ->width(60)
                    ->extraAttributes(['style' => 'object-fit: cover; border-radius: 0.375rem;'])
                    ->defaultImageUrl('https://via.placeholder.com/60x40?text=—'),
                Tables\Columns\TextColumn::make('sort_order')->label('#')->sortable()->extraCellAttributes(['class' => 'pdc-nowrap'])
                    ->formatStateUsing(fn ($state, CrmProperty $record) => $record->sort_order ?: $record->id),
                Tables\Columns\TextColumn::make('title')->label('Nosaukums')->searchable()->sortable()->weight('bold')
                    ->description(fn (CrmProperty $record): ?string => $record->clients()
                        ->wherePivot('relation', 'seller')
                        ->first()?->name),
                Tables\Columns\TextColumn::make('category')->label('Kategorija')->badge()->sortable(),
                Tables\Columns\TextColumn::make('status')->label('Statuss')
                    ->badge()
                    ->sortable()
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'published',
                        'warning' => 'expired',
                        'danger' => 'hidden',
                        'info' => 'sold',
                    ])
                    ->formatStateUsing(fn ($state) => CrmProperty::STATUSES[$state] ?? $state),
                Tables\Columns\TextColumn::make('city')->label('Pilsēta')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('kadastra_nr')->label('Kadastra nr.')->sortable()
                    ->placeholder(fn ($state) => $state ? null : '—')
                    ->icon(fn ($state) => $state ? null : 'heroicon-o-exclamation-triangle')
                    ->iconColor('warning'),
                Tables\Columns\TextColumn::make('price_eur')->label('Cena')->money('EUR')->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('owner.name')->label('Atbildīgais')->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Atjaunināts')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Statuss')->options(CrmProperty::STATUSES)->multiple()->preload()->searchable(),
                Tables\Filters\SelectFilter::make('category')->label('Kategorija')->options(CrmProperty::CATEGORIES)->multiple()->preload()->searchable(),
                Tables\Filters\SelectFilter::make('owner_user_id')->label('Aģents')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Actions\ViewAction::make()->label('Skatīt'),
                Actions\EditAction::make()->label('Rediģēt'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmProperties::route('/'),
            'create' => Pages\CreateCrmProperty::route('/create'),
            'view' => Pages\ViewCrmProperty::route('/{record}'),
            'edit' => Pages\EditCrmProperty::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ClientsRelationManager::class,
        ];
    }
}
