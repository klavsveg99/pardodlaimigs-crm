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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
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
            Grid::make(['default' => 1, 'lg' => 2])->schema([
                Section::make('Pamatdati')->columns(['default' => 1, 'md' => 2])->schema([
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

                    Forms\Components\Select::make('lead_source')
                        ->label('Līda avots')
                        ->options(CrmProperty::LEAD_SOURCES)
                        ->default('internal')
                        ->helperText('Ārējais līds — pienākas 10-20% no komisijas. Iekšējais (pardodlaimigs.lv) — bez maksas.')
                        ->required()
                        ->live(),

                    Forms\Components\TextInput::make('lead_owner')
                        ->label('Līda īpašnieks')
                        ->placeholder('Norādi līda īpašnieku')
                        ->maxLength(255)
                        ->required(fn (Get $get): bool => $get('lead_source') === 'external')
                        ->visible(fn (Get $get): bool => $get('lead_source') === 'external')
                        ->columnSpanFull(),

                    Grid::make(['default' => 1, 'md' => 3])
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
                        ->label('Aģents')
                        ->relationship('owner', 'name')
                        ->searchable()
                        ->preload(),
                ])->columnSpan(1),

                Section::make('Īpašuma dati')->columns(['default' => 1, 'md' => 2])->schema([
                    Forms\Components\TextInput::make('beds')->label('Istabas')->numeric(),
                    Forms\Components\TextInput::make('baths')->label('Vannas istabas')->numeric(),
                    Forms\Components\TextInput::make('size_m2')->label('Platība (m²)')->numeric(),
                    Forms\Components\TextInput::make('land_m2')->label('Zemes platība (m²)')->numeric(),
                    Forms\Components\TextInput::make('kadastra_nr')
                         ->label('Kadastra nr.*')
                         ->required()
                         ->numeric()
                         ->minLength(8)
                         ->maxLength(11)
                         ->rules('regex:/^\d{8,11}$/')
                         ->validationMessages([
                             'required' => 'Kadastra nr. ir obligāts lauks.',
                             'regex' => 'Kadastra nr. jābūt 8-11 cipariem.',
                             'min' => 'Kadastra nr. jābūt vismaz 8 cipariem.',
                             'max' => 'Kadastra nr. nedrīkst pārsniegt 11 ciparus.',
                         ])
                         ->columnSpanFull(),
                ])->columnSpan(1),
            ])->columnSpanFull(),

            Section::make('Atrašanās vieta')->columns(['default' => 1, 'md' => 2])->schema([
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

            Section::make('Apraksts')
                ->columnSpanFull()
                ->headerActions([
                    Actions\Action::make('ai_generate_description')
                        ->label('AI ģenerēt aprakstu')
                        ->icon('heroicon-o-sparkles')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Ģenerēt aprakstu ar AI')
                        ->modalDescription('Tiks izveidots īpašuma apraksts latviešu un angļu valodā, ņemot vērā ievadītos datus. Angļu tulkojums tiks veidots ar bezmaksas MyMemory API.')
                        ->modalSubmitActionLabel('Ģenerēt')
                        ->action(function (Get $get, Set $set): void {
                            $category = (string) ($get('category') ?? 'īpašums');
                            $city = (string) ($get('city') ?? '');
                            $address = (string) ($get('address') ?? '');
                            $price = (float) ($get('price_eur') ?? 0);
                            $beds = (int) ($get('beds') ?? 0);
                            $baths = (int) ($get('baths') ?? 0);
                            $size = (int) ($get('size_m2') ?? 0);
                            $land = (int) ($get('land_m2') ?? 0);
                            $kadastra = (string) ($get('kadastra_nr') ?? '');
                            $status = (string) ($get('status') ?? '');
                            $title = (string) ($get('title') ?? $category);

                            $type = strtolower($category);
                            $locParts = array_filter([$city, $address]);
                            $locText = $locParts ? ' — ' . implode(', ', $locParts) : '';

                            $feat = [];
                            if ($beds > 0) $feat[] = $beds . ' ist.';
                            if ($baths > 0) $feat[] = $baths . ' vannas ist.';
                            if ($size > 0) $feat[] = $size . ' m²';
                            if ($land > 0) $feat[] = 'zeme ' . $land . ' m²';
                            if ($kadastra) $feat[] = 'kadastra nr. ' . $kadastra;
                            $featText = $feat ? implode(' · ', $feat) : '';

                            if ($status === 'sold') {
                                $prefix = 'Pārdots';
                            } elseif ($status === 'published') {
                                $prefix = 'Pārdošanā';
                            } else {
                                $prefix = 'Piedāvājam';
                            }

                            // --- LV (bagātīgāks šablons) ---
                            $lvLines = [];
                            $lvLines[] = \Illuminate\Support\Str::ucfirst($prefix) . ' ' . strtolower($type) . $locText . ($featText ? '. ' . $featText . '.' : '.');
                            $lvLines[] = '';
                            $lvLines[] = $title ? '“' . $title . '” — mājīgs un pārdomāts piedāvājums, kas piemērots gan dzīvošanai, gan investīcijai.' : '';
                            if ($price > 0) {
                                $lvLines[] = 'Cena: ' . number_format($price, 0, ',', ' ') . ' €.';
                                $lvLines[] = '';
                            }
                            $lvLines[] = 'Īpašums izceļas ar labu atrašanās vietu, sakārtotu dokumentāciju un iespēju pielāgot telpas savām vajadzībām. Plašāks apraksts un foto — pielikumos.';
                            $lvLines[] = '';
                            $lvLines[] = 'Interesē šis īpašums? Sazinies ar mums, lai pieteiktu apskati un uzzinātu vairāk!';
                            $lvLines[] = '';
                            $lvLines[] = 'Pārdod Laimīgs — nekustamo īpašumu aģentūra.';
                            $lvText = implode("\n", array_filter($lvLines, fn($l) => $l !== null));

                            // --- EN via MyMemory free API (lv -> en) ---
                            $enText = null;
                            try {
                                $resp = \Illuminate\Support\Facades\Http::timeout(8)->get('https://api.mymemory.translated.net/get', [
                                    'q' => $lvText,
                                    'langpair' => 'lv|en',
                                ]);
                                if ($resp->successful()) {
                                    $data = $resp->json();
                                    $translated = $data['responseData']['translatedText'] ?? null;
                                    if ($translated && $translated !== $lvText) {
                                        $enText = $translated;
                                    }
                                }
                            } catch (\Throwable $e) {
                                // fallback to template
                            }
                            if (!$enText) {
                                // template fallback EN
                                $enLines = [];
                                $enLines[] = ucfirst($prefix) . ' ' . $type . ($city ? ' in ' . $city : '') . ($featText ? '. ' . $featText . '.' : '.');
                                $enLines[] = '';
                                if ($title) $enLines[] = '"' . $title . '" — a cozy, well-planned property suitable for living or investment.';
                                if ($price > 0) { $enLines[] = 'Price: ' . number_format($price, 0, ',', ' ') . ' EUR.'; $enLines[] = ''; }
                                $enLines[] = 'The property benefits from a good location, tidy documentation and flexible layout options. See more in the attachments.';
                                $enLines[] = '';
                                $enLines[] = 'Interested? Contact us to schedule a viewing!';
                                $enLines[] = '';
                                $enLines[] = 'Pārdod Laimīgs — real estate agency.';
                                $enText = implode("\n", array_filter($enLines, fn($l) => $l !== null));
                            }

                            $html = '<p><strong>[LV]</strong><br>' . nl2br(e($lvText)) . '</p>'
                                . '<p><strong>[EN]</strong><br>' . nl2br(e($enText)) . '</p>';

                            $set('description', $html);
                        }),
                ])
                ->schema([
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
                Tables\Columns\TextColumn::make('category')->label('Kategorija')->sortable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('status')->label('Statuss')
                    ->badge()
                    ->sortable()
                    ->color('gray')
                    ->formatStateUsing(fn ($state) => CrmProperty::STATUSES[$state] ?? $state),
                Tables\Columns\TextColumn::make('city')->label('Pilsēta')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('kadastra_nr')->label('Kadastra nr.')->sortable()
                    ->placeholder(fn ($state) => $state ? null : '—')
                    ->icon(fn ($state) => $state ? null : 'heroicon-o-exclamation-triangle')
                    ->iconColor('warning'),
                Tables\Columns\TextColumn::make('price_eur')->label('Cena')->money('EUR')->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('owner.name')->label('Aģents')->sortable(),
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
            ->filtersFormColumns(3)
            ->actions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()->label('Skatīt')->color('gray'),
                    Actions\EditAction::make()->label('Rediģēt')->color('gray'),
                ])->color('gray'),
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
            \App\Filament\Admin\Resources\CrmPropertyResource\RelationManagers\ClientsRelationManager::class,
        ];
    }
}
