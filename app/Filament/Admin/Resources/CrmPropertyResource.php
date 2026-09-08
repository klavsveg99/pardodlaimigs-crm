<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CrmPropertyResource\Pages;
use App\Filament\Admin\Resources\CrmPropertyResource\RelationManagers\ClientsRelationManager;
use App\Filament\Forms\Components\AttachmentsGrid;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
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
                        ->label('Pieteikuma avots')
                        ->options(CrmProperty::LEAD_SOURCES)
                        ->default('internal')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set): void {
                            if ($state !== 'external') {
                                $set('lead_owner', null);
                            }
                        })
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('lead_owner')
                        ->label('Atbildīgais aģents')
                        ->placeholder('Norādi atbildīgo aģentu')
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

                                    return number_format($percent, 2, ',', ' ').' %';
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
                        ->label('Kadastra nr.')
                        ->required()
                        ->string()
                        ->maxLength(11)
                        ->minLength(11)
                        ->rules(['regex:/^\d{11}$/'])
                        // NOTE: do NOT add an x-on:paste handler that calls
                        // preventDefault() and assigns $el.value — programmatic
                        // assignment never fires an `input` event, so Livewire's
                        // wire:model never syncs the pasted value and Save fails
                        // with "Kadastra nr. ir obligāts lauks" while the input
                        // visibly holds 11 digits. The input handler below
                        // already strips/slices whatever paste inserts.
                        ->extraInputAttributes([
                            'maxlength' => 11,
                            'inputmode' => 'numeric',
                            'autocomplete' => 'off',
                            'x-on:input' => 'let v = $el.value.replace(/\\D/g, \'\').slice(0, 11); if (v !== $el.value) { $el.value = v; }',
                        ])
                        ->placeholder('01000250003')
                        // Belt & braces: normalize server-side too, so even if
                        // the browser state somehow holds formatted input
                        // (spaces, dashes), validation sees exactly the digits.
                        ->mutateStateForValidationUsing(fn ($state): ?string => filled($state) ? preg_replace('/\D/', '', (string) $state) : $state)
                        ->dehydrateStateUsing(fn ($state): ?string => filled($state) ? preg_replace('/\D/', '', (string) $state) : null)
                        ->validationMessages([
                            'required' => 'Kadastra nr. ir obligāts lauks.',
                            'regex' => 'Kadastra nr. jābūt tieši 11 cipariem.',
                            'min' => 'Kadastra nr. jābūt tieši 11 cipariem.',
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
                        ->modalDescription('Tiks izveidots īpašuma apraksts latviešu un angļu valodā, ņemot vērā ievadītos datus. Angļu versija tiek veidota no šablona (bez ārējiem API, lai izvairītos no rate-limit).')
                        ->modalSubmitActionLabel('Ģenerēt')
                        ->action(function (Get $get, Set $set): void {
                            $category = (string) ($get('data.category') ?? 'īpašums');
                            $city = (string) ($get('data.city') ?? '');
                            $address = (string) ($get('data.address') ?? '');
                            $price = (float) ($get('data.price_eur') ?? 0);
                            $beds = (int) ($get('data.beds') ?? 0);
                            $baths = (int) ($get('data.baths') ?? 0);
                            $size = (int) ($get('size_m2') ?? 0);
                            $land = (int) ($get('land_m2') ?? 0);
                            $kadastra = (string) ($get('data.kadastra_nr') ?? '');
                            $status = (string) ($get('data.status') ?? '');
                            $leadSource = (string) ($get('data.lead_source') ?? '');
                            $leadOwner = (string) ($get('data.lead_owner') ?? '');
                            $finalPrice = (float) ($get('data.final_price_eur') ?? 0);
                            $commission = (float) ($get('data.commission_eur') ?? 0);
                            $title = (string) ($get('data.title') ?? $category);

                            $type = strtolower($category);
                            $locParts = array_filter([$city, $address]);
                            $locText = $locParts ? ' — '.implode(', ', $locParts) : '';

                            $feat = [];
                            if ($beds > 0) {
                                $feat[] = $beds.' ist.';
                            }
                            if ($baths > 0) {
                                $feat[] = $baths.' vannas ist.';
                            }
                            if ($size > 0) {
                                $feat[] = $size.' m²';
                            }
                            if ($land > 0) {
                                $feat[] = 'zeme '.$land.' m²';
                            }
                            if ($kadastra) {
                                $feat[] = 'kadastra nr. '.$kadastra;
                            }
                            if ($leadSource) {
                                $feat[] = 'avots: '.$leadSource;
                            }
                            if ($leadOwner) {
                                $feat[] = 'atbildīgais aģents: '.$leadOwner;
                            }
                            if ($finalPrice > 0) {
                                $feat[] = 'gala cena: '.number_format($finalPrice, 0, ',', ' ').' €';
                            }
                            if ($commission > 0) {
                                $feat[] = 'komisija: '.number_format($commission, 0, ',', ' ').' €';
                            }
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
                            $lvLines[] = Str::ucfirst($prefix).' '.strtolower($type).$locText.($featText ? '. '.$featText.'.' : '.');
                            $lvLines[] = '';
                            $lvLines[] = $title ? '“'.$title.'” — mājīgs un pārdomāts piedāvājums, kas piemērots gan dzīvošanai, gan investīcijai.' : '';
                            if ($price > 0) {
                                $lvLines[] = 'Cena: '.number_format($price, 0, ',', ' ').' €.';
                                $lvLines[] = '';
                            }
                            $lvLines[] = 'Īpašums izceļas ar labu atrašanās vietu, sakārtotu dokumentāciju un iespēju pielāgot telpas savām vajadzībām. Plašāks apraksts un foto — pielikumos.';
                            $lvLines[] = '';
                            $lvLines[] = 'Interesē šis īpašums? Sazinies ar mums, lai pieteiktu apskati un uzzinātu vairāk!';
                            $lvLines[] = '';
                            $lvLines[] = 'Pārdod Laimīgs — nekustamo īpašumu aģentūra.';
                            $lvText = implode("\n", array_filter($lvLines, fn ($l) => $l !== null));

                            // --- EN: pure template (no MyMemory call — was rate-limited) ---
                            $typeEn = match (strtolower($category)) {
                                'dzīvoklis' => 'apartment',
                                'māja' => 'house',
                                'zeme' => 'land plot',
                                'mežs' => 'forest',
                                'lauksaimniecības zeme' => 'agricultural land',
                                'komerciāls' => 'commercial property',
                                default => 'property',
                            };
                            $prefixEn = match ($status) {
                                'sold' => 'Sold',
                                'published' => 'For sale',
                                'deleted' => 'Withdrawn',
                                default => 'Offered',
                            };
                            $enLines = [];
                            $enLines[] = $prefixEn.': '.$typeEn.($city ? ' in '.$city : '').($featText ? '. '.$featText.'.' : '.');
                            $enLines[] = '';
                            if ($title) {
                                $enLines[] = '"'.$title.'" — a well-planned property suitable for living or investment.';
                            }
                            if ($price > 0) {
                                $enLines[] = 'Price: EUR '.number_format($price, 0, ',', ' ').'.';
                                $enLines[] = '';
                            }
                            $enLines[] = 'The property benefits from a good location, tidy documentation and a flexible layout. See more details and photos in the attachments.';
                            $enLines[] = '';
                            $enLines[] = 'Interested? Get in touch to schedule a viewing!';
                            $enLines[] = '';
                            $enLines[] = 'Pārdod Laimīgs — real estate agency.';
                            $enText = implode("\n", array_filter($enLines, fn ($l) => $l !== null));

                            $html = '<p><strong>[LV]</strong><br>'.nl2br(e($lvText)).'</p>'
                                .'<p><strong>[EN]</strong><br>'.nl2br(e($enText)).'</p>';

                            $set('description', $html);
                        }),
                ])
                ->schema([
                    Tabs::make()
                        ->tabs([
                            Tab::make('Apraksts')
                                ->icon('heroicon-o-pencil-square')
                                ->schema([
                                    Forms\Components\RichEditor::make('description')
                                        ->hiddenLabel()
                                        ->extraInputAttributes(['style' => 'min-height: 280px'])
                                        ->columnSpanFull(),
                                ]),
                            Tab::make('Versijas')
                                ->icon('heroicon-o-clock')
                                ->badge(fn (?CrmProperty $record): ?string => $record ? (string) $record->descriptionRevisions()->count() : null)
                                ->visible(fn (?CrmProperty $record): bool => $record !== null)
                                ->schema([
                                    View::make('filament.forms.components.description-revisions')
                                        ->columnSpanFull(),
                                ]),
                        ])
                        ->columnSpanFull(),
                ])->columnSpanFull(),

            Section::make('Pielikumi')->columnSpanFull()->schema([
                AttachmentsGrid::make('attachments')
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
                            return $first->cacheBustedUrl();
                        }
                        $urls = $record->image_urls ?? [];

                        return is_array($urls) && ! empty($urls[0]) ? $urls[0] : null;
                    })
                    ->height(40)
                    ->width(60)
                    ->extraAttributes(['style' => 'object-fit: cover; border-radius: 0.375rem;'])
                    ->defaultImageUrl('https://via.placeholder.com/60x40?text=—'),
                Tables\Columns\TextColumn::make('title')->label('Nosaukums')->searchable()->sortable()->weight('bold')
                    ->url(fn (CrmProperty $record) => static::getUrl('view', ['record' => $record]))
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
            ClientsRelationManager::class,
        ];
    }
}
