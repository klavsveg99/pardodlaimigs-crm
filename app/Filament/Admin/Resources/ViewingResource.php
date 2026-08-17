<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ViewingResource\Pages;
use App\Models\Client;
use App\Models\PropertyCache;
use App\Models\Viewing;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class ViewingResource extends Resource
{
    protected static ?string $model = Viewing::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Apskates';

    protected static string|UnitEnum|null $navigationGroup = 'Darbplūsma';

    protected static ?string $modelLabel = 'Apskate';

    protected static ?string $pluralModelLabel = 'Apskates';

    protected static ?int $navigationSort = 31;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
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
                        ->where('status', 'publish')
                        ->orderBy('title');

                    if ($component->getRecord()?->property_id) {
                        $query->orWhere('id', $component->getRecord()->property_id);
                    }

                    return $query->get()->mapWithKeys(fn (PropertyCache $property) => [$property->id => $property->selection_label])->all();
                })
                ->required()
                ->default(request()->query('property_id')),
            Forms\Components\Select::make('client_id')->label('Klients')
                ->searchable()
                ->required()
                ->options(fn () => Client::query()->orderBy('name')->limit(20)->pluck('name', 'id')->all())
                ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name),
            Forms\Components\DateTimePicker::make('scheduled_at')->label('Kad')->native(false)->required(),
            Forms\Components\TextInput::make('duration_min')->label('Ilgums (min)')->numeric()->default(30),
            Forms\Components\Select::make('agent_user_id')->label('Aģents')
                ->relationship('agent', 'name')->searchable()->preload()->optionsLimit(20),
            Forms\Components\Select::make('status')->label('Statuss')->options([
                'scheduled' => 'Ieplānota',
                'done' => 'Notikusi',
                'cancelled' => 'Atcelta',
                'no_show' => 'Neatnāca',
            ])->default('scheduled'),
            Forms\Components\Textarea::make('notes_md')->label('Piezīmes')->rows(3)->columnSpanFull(),
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
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['property', 'client', 'agent']))
            ->columns([
                Tables\Columns\TextColumn::make('scheduled_at')
                    ->label('Kad')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->color(fn ($record) => $record->status === 'scheduled' && $record->scheduled_at->isPast() ? 'danger' : null),
                Tables\Columns\TextColumn::make('property.title')->label('Īpašums')->limit(40),
                Tables\Columns\TextColumn::make('client.name')->label('Klients')->searchable(),
                Tables\Columns\TextColumn::make('agent.name')->label('Aģents'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statuss')
                    ->badge()
                    ->colors([
                        'info' => 'scheduled',
                        'success' => 'done',
                        'danger' => 'cancelled',
                        'warning' => 'no_show',
                    ])
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'scheduled' => 'Ieplānota',
                        'done' => 'Notikusi',
                        'cancelled' => 'Atcelta',
                        'no_show' => 'Neatnāca',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('duration_min')->label('Min')->alignCenter(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'scheduled' => 'Ieplānota', 'done' => 'Notikusi',
                    'cancelled' => 'Atcelta', 'no_show' => 'Neatnāca',
                ]),
                Tables\Filters\Filter::make('upcoming')->label('Gaidāmās')->query(
                    fn ($query) => $query->where('scheduled_at', '>=', now())->where('status', 'scheduled')
                ),
            ])
            ->actions([
                Actions\EditAction::make()->label('Rediģēt'),
            ])
            ->defaultSort('scheduled_at');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListViewings::route('/'),
            'create' => Pages\CreateViewing::route('/create'),
            'edit' => Pages\EditViewing::route('/{record}/edit'),
        ];
    }
}
