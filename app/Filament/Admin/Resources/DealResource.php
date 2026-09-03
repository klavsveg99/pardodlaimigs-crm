<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DealResource\Pages;
use App\Models\Client;
use App\Models\Deal;
use App\Models\PropertyCache;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class DealResource extends Resource
{
    protected static ?string $model = Deal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-euro';

    protected static ?string $navigationLabel = 'Darījumi';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $modelLabel = 'Darījums';

    protected static ?string $pluralModelLabel = 'Darījumi';

    protected static ?int $navigationSort = 20;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->schema([
            Forms\Components\TextInput::make('title')->label('Nosaukums')->maxLength(255)->columnSpanFull(),
            Forms\Components\Select::make('client_id')->label('Klients')
                ->searchable()
                ->required()
                ->options(fn () => Client::query()->orderBy('name')->limit(20)->pluck('name', 'id')->all())
                ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name),
            Forms\Components\Select::make('property_id')->label('Īpašums')
                ->searchable()
                ->getSearchResultsUsing(function (string $search) {
                    return PropertyCache::query()
                        ->where(function ($q) use ($search) {
                            $q->where('title', 'like', "%{$search}%")
                                ->orWhere('city', 'like', "%{$search}%")
                                ->orWhere('kadastra_nr', 'like', "%{$search}%")
                                ->orWhere('id', '=', $search);
                        })
                        ->orderBy('title')
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(fn (PropertyCache $p) => [$p->id => $p->selection_label])
                        ->toArray();
                })
                ->getOptionLabelUsing(fn ($value): ?string => PropertyCache::find($value)?->selection_label)
                ->options(function (mixed $state, Forms\Components\Select $component): array {
                    $query = PropertyCache::query()
                        ->orderBy('title');
                    if ($component->getRecord()?->property_id) {
                        $query->where(fn ($q) => $q->where('status', 'publish')->orWhere('id', $component->getRecord()->property_id));
                    } else {
                        $query->where('status', 'publish');
                    }

                    return $query->get()->mapWithKeys(fn (PropertyCache $property) => [$property->id => $property->selection_label])->all();
                }),
            Forms\Components\Select::make('stage')->label('Posms')
                ->options(Deal::STAGES)->default('jauns')->required(),
            Forms\Components\TextInput::make('value_eur')->label('Vērtība (€)')->numeric()->prefix('€'),
            Forms\Components\Select::make('owner_user_id')->label('Aģents')
                ->relationship('owner', 'name')->searchable()->preload()->optionsLimit(20),
            Forms\Components\FileUpload::make('attachments')
                ->label('Pielikumi')
                ->helperText('Atļauti failu tipi: '.implode(', ', config('attachments.accepted_mimes'))
                    .' · maksimālais izmērs: '.(int) (config('attachments.max_size_kb') / 1024).' MB')
                ->multiple()
                ->reorderable()
                ->deletable()
                ->previewable()
                ->openable()
                ->storeFileNamesIn('attachment_original_names')
                ->acceptedFileTypes(config('attachments.accepted_file_types'))
                ->maxSize((int) config('attachments.max_size_kb'))
                ->disk('public')
                ->directory('attachments')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('title')->label('Nosaukums')->searchable()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('client.name')->label('Klients')->searchable()->weight('bold')->wrap(),
                Tables\Columns\TextColumn::make('property.selection_label')->label('Īpašums')->limit(60)->sortable()->wrap()->placeholder('—'),
                Tables\Columns\TextColumn::make('stage')
                    ->label('Posms')
                    ->badge()
                    ->sortable()
                    ->colors([
                        'info' => ['jauns', 'tirgosana'],
                        'warning' => 'pirma_tiksanas',
                        'primary' => 'noslegta_sadarbiba',
                        'gray' => 'foto_video',
                        'danger' => 'dokumentu_saskanosana',
                        'success' => 'pardots',
                    ])
                    ->formatStateUsing(fn ($state) => Deal::STAGES[$state] ?? $state),
                Tables\Columns\TextColumn::make('value_eur')
                    ->label('Vērtība')
                    ->extraCellAttributes(['class' => 'pdc-nowrap'])
                    ->money('EUR')->sortable(),
                Tables\Columns\TextColumn::make('owner.name')->label('Aģents')->sortable()->wrap(),
                Tables\Columns\TextColumn::make('updated_at')->label('Atjaunināts')->since()->extraCellAttributes(['class' => 'pdc-nowrap']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->label('Posms')
                    ->options(Deal::STAGES),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()->label('Skatīt'),
                    Actions\EditAction::make()->label('Rediģēt'),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Deal::where('stage', '!=', 'pardots')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeals::route('/'),
            'create' => Pages\CreateDeal::route('/create'),
            'view' => Pages\ViewDeal::route('/{record}'),
            'edit' => Pages\EditDeal::route('/{record}/edit'),
        ];
    }
}
