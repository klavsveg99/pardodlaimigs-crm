<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ViewingResource\Pages;
use App\Filament\Forms\Components\AttachmentsGrid;
use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\Viewing;
use App\Support\AgentField;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use UnitEnum;

class ViewingResource extends Resource
{
    public static function canAccess(): bool
    {
        return ! auth()->user()?->isPhoto();
    }

    protected static ?string $model = Viewing::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Apskates';

    protected static string|UnitEnum|null $navigationGroup = 'Darbplūsma';

    protected static ?string $modelLabel = 'Apskate';

    protected static ?string $pluralModelLabel = 'Apskates';

    protected static ?int $navigationSort = 30;

    // Aģenti redz tikai savas apskates — arī atverot tiešu URL.
    public static function getEloquentQuery(): EloquentBuilder
    {
        return parent::getEloquentQuery()->when(
            ! auth()->user()?->can('manage'),
            fn ($query) => $query->where('agent_user_id', auth()->id()),
        );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('property_id')->label('Īpašums')
                ->searchable()
                ->options(function (mixed $state, Forms\Components\Select $component): array {
                    $query = CrmProperty::query()
                        ->where('status', '!=', 'deleted')
                        ->orderByDesc('id')
                        ->limit(100);

                    return $query->get()
                        ->mapWithKeys(fn (CrmProperty $p) => [$p->id => $p->selection_label])
                        ->all();
                })
                ->getOptionLabelUsing(fn ($value): ?string => CrmProperty::find($value)?->selection_label)
                ->getSearchResultsUsing(fn (string $search): array => CrmProperty::query()
                    ->where('status', '!=', 'deleted')
                    ->where(fn ($q) => $q->where('title', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('kadastra_nr', 'like', "%{$search}%"))
                    ->orderByDesc('id')
                    ->limit(50)
                    ->get()
                    ->mapWithKeys(fn (CrmProperty $p) => [$p->id => $p->selection_label])
                    ->all())
                ->required()
                ->default(request()->query('property_id')),
            Forms\Components\Select::make('client_id')->label('Klients')
                ->searchable()
                ->required()
                ->options(fn () => Client::query()->orderBy('name')->limit(20)->pluck('name', 'id')->all())
                ->getSearchResultsUsing(fn (string $search): array => Client::query()
                    ->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%"))
                    ->orderBy('name')
                    ->limit(50)
                    ->pluck('name', 'id')
                    ->all())
                ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name),
            // "Nevēlāks kā tagad" tikai izveidojot — pretējā gadījumā vecas
            // apskates saglabāšana editā neizdodas (statuss mainās vēlāk).
            Forms\Components\DateTimePicker::make('scheduled_at')->label('Kad')->native(false)
                ->minDate(fn (string $operation) => $operation === 'create' ? now() : null)
                ->required(),
            Forms\Components\TextInput::make('duration_min')->label('Ilgums (min)')->numeric()->default(30),
            Forms\Components\Select::make('agent_user_id')->label('Aģents')
                ->relationship('agent', 'name', modifyQueryUsing: fn (EloquentBuilder $query) => $query->assignable())->searchable()->preload()->optionsLimit(20)
                ->visible(AgentField::visible())
                ->disabled(AgentField::disabled()),
            Forms\Components\Select::make('status')->label('Statuss')->options([
                'scheduled' => 'Ieplānota',
                'done' => 'Notikusi',
                'cancelled' => 'Atcelta',
                'no_show' => 'Neatnāca',
            ])->default('scheduled'),
            Forms\Components\Textarea::make('notes_md')->label('Piezīmes')->rows(3)->columnSpanFull(),
            AttachmentsGrid::make('attachments')
                ->label('Pielikumi')
                ->helperText('Atļautie failu tipi: '.implode(', ', config('attachments.accepted_mimes'))
                    .' · maksimālais izmērs: '.(int) (config('attachments.max_size_kb') / 1024).' MB')
                ->reorderable(false)
                ->multiselect(false)
                ->deletable()
                ->collection('gallery')
                ->recordSendable()
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
                    ->extraCellAttributes(['class' => 'pdc-nowrap'])
                    ->color(fn ($record) => $record->status === 'scheduled' && $record->scheduled_at->isPast() ? 'danger' : null)
                    ->icon(fn ($record) => $record->status === 'scheduled' && $record->scheduled_at->isPast() ? 'heroicon-o-exclamation-triangle' : null)
                    ->iconColor('warning'),
                Tables\Columns\TextColumn::make('property.title')->label('Īpašums')->limit(40)->sortable()
                    ->url(fn ($record) => route('filament.admin.resources.viewings.edit', $record)),
                Tables\Columns\TextColumn::make('client.name')->label('Klients')->searchable()->sortable()
                    ->url(fn ($record) => $record->client_id ? route('filament.admin.resources.clients.view', $record->client_id) : null),
                Tables\Columns\TextColumn::make('agent.name')->label('Aģents')->sortable()
                    ->url(fn ($record) => $record->agent_user_id ? route('filament.admin.resources.users.edit', $record->agent_user_id) : null),
                Tables\Columns\SelectColumn::make('status')
                    ->label('Statuss')
                    ->options([
                        'scheduled' => 'Ieplānota',
                        'done' => 'Notikusi',
                        'cancelled' => 'Atcelta',
                        'no_show' => 'Neatnāca',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_min')->label('Min')->sortable()->extraCellAttributes(['class' => 'pdc-nowrap']),
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
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->label('Rediģēt')->color('gray'),
                    Actions\DeleteAction::make()->label('Dzēst')->color('danger'),
                ])->color('gray'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('set_status')
                        ->label('Mainīt statusu')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label('Statuss')
                                ->options([
                                    'scheduled' => 'Ieplānota',
                                    'done' => 'Notikusi',
                                    'cancelled' => 'Atcelta',
                                    'no_show' => 'Neatnāca',
                                ])
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each->update(['status' => $data['status']]);

                            Notification::make()
                                ->title($records->count().' apskates atjauninātas')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Actions\DeleteBulkAction::make()->label('Dzēst')->color('danger'),
                ]),
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
