<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CrmPropertyResource\Pages;
use App\Filament\Admin\Resources\CrmPropertyResource\RelationManagers\ClientsRelationManager;
use App\Filament\Forms\Components\AttachmentsGrid;
use App\Models\CrmProperty;
use App\Services\Ai\DescriptionGenerator;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
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
use Illuminate\Support\Facades\Storage;
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
                        // Kadastre nav prasīts dzēšanas plūsmai — statuss "Dzēsts"
                        // jāvar saglabāt bez kadastra numura (CRM 2.1, #4).
                        ->required(fn (Get $get): bool => ($get('status') ?? '') !== 'deleted')
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
                    ->label('Pilsēta/rajons')
                    ->maxLength(128)
                    ->required()
                    ->validationMessages([
                        'required' => 'Pilsēta/rajons ir obligāts lauks.',
                    ]),

                Forms\Components\TextInput::make('address')
                    ->label('Adrese')
                    ->maxLength(255)
                    ->required()
                    ->validationMessages([
                        'required' => 'Adrese ir obligāts lauks.',
                    ]),

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
                    // Standarta Filament modāls ar CRM datu pārskatu un
                    // 4 papildu ievadēm — zaļa poga WYSIWYG bloka galvenē.
                    // CRM 2.2: modāls rāda "Informācija no CRM" (automātiski
                    // savāktie dati), lai aģentam nav jādomā, kur ko ievadīt.
                    Actions\Action::make('ai_generator')
                        ->visible(fn (): bool => DescriptionGenerator::provider() !== null)
                        ->label('AI ģenerēt aprakstu')
                        ->icon('heroicon-o-sparkles')
                        ->color('primary')
                        ->modalHeading('AI īpašuma apraksta ģenerators')
                        ->modalSubmitActionLabel('Ģenerēt aprakstu')
                        ->form([
                            Forms\Components\Placeholder::make('crm_info')
                                ->hiddenLabel()
                                ->content(fn ($livewire, ?CrmProperty $record) => view('filament.forms.components.ai-crm-info', [
                                    'rows' => self::aiCrmInfoRows(is_array($livewire->data ?? null) ? $livewire->data : [], $record),
                                ])),
                            Forms\Components\Textarea::make('advantages')
                                ->label('Priekšrocības')
                                ->rows(3)
                                ->helperText('Atrašanās vieta, piekļuve, infrastruktūra, skats, ūdens, daba u.c.'),
                            Forms\Components\Textarea::make('technical')
                                ->label('Tehniskā informācija')
                                ->rows(3)
                                ->helperText('Apkure, ūdens, kanalizācija, elektrība, ēkas stāvoklis, būvniecības gads'),
                            Forms\Components\Textarea::make('investment')
                                ->label('Investīciju potenciāls')
                                ->rows(3)
                                ->helperText('Attīstības iespējas, zemes izmantošana, loģistika, komerciālais potenciāls'),
                            Forms\Components\Textarea::make('extra')
                                ->label('Papildu informācija')
                                ->rows(3)
                                ->helperText('Brīvs teksts — viss svarīgais, ko AI vēl jāņem vērā'),
                        ])
                        ->fillForm(fn (?CrmProperty $record): array => [
                            'advantages' => $record?->ai_notes['advantages'] ?? null,
                            'technical' => $record?->ai_notes['technical'] ?? null,
                            'investment' => $record?->ai_notes['investment'] ?? null,
                            'extra' => $record?->ai_notes['extra'] ?? null,
                        ])
                        ->action(fn (array $data, $livewire) => $livewire->runAiGenerator($data)),
                    // Rezerves šablona ģenerators — redzams tikai, ja nav
                    // konfigurēta neviena AI atslēga (GEMINI/OPENAI).
                    Actions\Action::make('ai_generate_description')
                        ->visible(fn (): bool => blank(config('services.gemini.key')) && blank(config('services.openai.key')))
                        ->label('Ģenerēt (šablonā)')
                        ->icon('heroicon-o-sparkles')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Ģenerēt aprakstu ar šablonu (bez AI)')
                        ->modalDescription('Tiks izveidots īpašuma apraksts latviešu un angļu valodā no šablona.')
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

                    // Rezultātu popup (Kopēt / Ievietot / variantu pogas). Ievades
                    // modālā ir Apraksts sekcijas galvenes AI darbība.
                    View::make('filament.forms.components.ai-generator-panel')
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

    /**
     * "Informācija no CRM" dati AI modālam (CRM 2.2): automātiski savāktie
     * īpašuma lauki no pašreizējās formas stāvokļa (ar kritumu uz saglabāto
     * ierakstu, ja formas lauks vēl nav aizpildīts — svarīgi Create lapā).
     *
     * @return array<string, string> tikai aizpildītie lauki
     */
    private static function aiCrmInfoRows(array $data, ?CrmProperty $record): array
    {
        $value = function (string $key) use ($data, $record) {
            $fromForm = array_key_exists($key, $data) && filled($data[$key]) ? $data[$key] : null;

            return filled($fromForm) ? $fromForm : ($record?->{$key} ?? null);
        };

        $rows = [
            'Nosaukums' => $value('title'),
            'Kategorija' => $value('category'),
            'Statuss' => CrmProperty::STATUSES[$value('status')] ?? null,
            'Cena' => ($price = $value('price_eur')) ? number_format((float) $price, 0, ',', ' ').' €' : null,
            'Istabas' => $value('beds'),
            'Vannas istabas' => $value('baths'),
            'Platība' => ($size = $value('size_m2')) ? ((string) $size).' m²' : null,
            'Zemes platība' => ($land = $value('land_m2')) ? ((string) $land).' m²' : null,
            'Kadastra nr.' => $value('kadastra_nr'),
            'Pilsēta/rajons' => $value('city'),
            'Adrese' => $value('address'),
        ];

        return array_filter($rows, fn ($v): bool => filled($v));
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
                    ->extraAttributes(['style' => 'object-fit: cover; border-radius: 0.375rem;']),
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
                Tables\Columns\TextColumn::make('city')->label('Pilsēta/rajons')->sortable()->searchable(),
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
                    // Mīkstā dzēšana (CRM 2.1, #1): statuss "Dzēsts" — dati,
                    // bildes un vēsture paliek; WP sinhronizācija automātiski
                    // noņem īpašumu no mājaslapas (deleted → draft).
                    Actions\Action::make('delete_property')
                        ->label('Dzēst')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn (CrmProperty $record): bool => $record->status !== 'deleted')
                        ->requiresConfirmation()
                        ->modalHeading('Vai tiešām dzēst šo īpašumu?')
                        ->modalDescription('Īpašums tiks pārvietots uz "Dzēstie" un noņemts no mājaslapas. Datus varēs atjaunot.')
                        ->modalSubmitActionLabel('Dzēst')
                        ->action(function (CrmProperty $record): void {
                            $record->update(['status' => 'deleted']);
                            Notification::make()
                                ->title('Īpašums dzēsts')
                                ->body('Pārvietots uz "Dzēstie".')
                                ->success()
                                ->send();
                        }),
                    Actions\Action::make('restore_property')
                        ->label('Atjaunot')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->visible(fn (CrmProperty $record): bool => $record->status === 'deleted')
                        ->requiresConfirmation()
                        ->modalHeading('Atjaunot īpašumu?')
                        ->modalDescription('Īpašums atgriezīsies kā melnraksts un būs redzams aktīvo īpašumu sarakstā.')
                        ->modalSubmitActionLabel('Atjaunot')
                        ->action(function (CrmProperty $record): void {
                            $record->update(['status' => 'draft']);
                            Notification::make()
                                ->title('Īpašums atjaunots')
                                ->success()
                                ->send();
                        }),
                    // Neatgriezeniska dzēšana (CRM 2.2, #1): tikai "Dzēstie"
                    // sadaļā un tikai administratoram. Atšķirībā no "Dzēst"
                    // (statuss → "Dzēsts") šī darbība ierakstu IZŅEM no CRM —
                    // bildes dzēš no diska, pivot un apraksta versijas kaskādē
                    // datubāzē; WP pusē īpašums jau ir atspiests (statuss
                    // "Dzēsts") un pēc dzēšanas pazūd no feeda.
                    Actions\Action::make('force_delete_property')
                        ->label('Izdzēst neatgriezeniski')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn (CrmProperty $record): bool => $record->status === 'deleted'
                            && auth()->user()?->can('manage'))
                        ->requiresConfirmation()
                        ->modalHeading('Vai tiešām vēlaties neatgriezeniski dzēst šo īpašumu?')
                        ->modalDescription('Pēc dzēšanas īpašumu vairs nebūs iespējams atjaunot un tas tiks pilnībā izņemts no CRM sistēmas.')
                        ->modalSubmitActionLabel('Izdzēst neatgriezeniski')
                        ->action(function (CrmProperty $record): void {
                            foreach ($record->attachments()->get() as $attachment) {
                                if (str_starts_with($attachment->path, 'http://') || str_starts_with($attachment->path, 'https://')) {
                                    continue;
                                }
                                Storage::disk($attachment->disk)->delete($attachment->path);
                            }
                            $record->attachments()->delete();
                            $record->delete();

                            Notification::make()
                                ->title('Īpašums neatgriezeniski izdzēsts')
                                ->body('Īpašums un visi saistītie dati ir pilnībā izņemti no CRM.')
                                ->success()
                                ->send();
                        }),
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
