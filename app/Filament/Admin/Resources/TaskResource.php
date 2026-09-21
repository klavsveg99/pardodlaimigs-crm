<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TaskResource\Pages;
use App\Filament\Forms\Components\AttachmentsGrid;
use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\Izpilditajs;
use App\Models\Task;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use UnitEnum;

class TaskResource extends Resource
{
    public static function canAccess(): bool
    {
        return ! auth()->user()?->isPhoto();
    }

    protected static ?string $model = Task::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Uzdevumi';

    protected static string|UnitEnum|null $navigationGroup = 'Darbplūsma';

    protected static ?string $modelLabel = 'Uzdevums';

    protected static ?string $pluralModelLabel = 'Uzdevumi';

    protected static ?int $navigationSort = 20;

    // Aģenti redz tikai savam aģentam piesaistītos uzdevumus — arī
    // atverot tiešu URL.
    public static function getEloquentQuery(): EloquentBuilder
    {
        return parent::getEloquentQuery()->when(
            ! auth()->user()?->can('manage'),
            fn ($query) => $query->where('assigned_user_id', auth()->id()),
        );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('title')->label('Nosaukums')->required()->columnSpanFull(),
            Forms\Components\Textarea::make('body')->label('Apraksts')->rows(3)->columnSpanFull(),
            // "Nevēlāks kā tagad" tikai izveidojot — pretējā gadījumā veca
            // (nokavēta) uzdevuma saglabāšana editā neizdodas.
            Forms\Components\DateTimePicker::make('due_at')->label('Līdz')->native(false)
                ->minDate(fn (string $operation) => $operation === 'create' ? now() : null)
                ->required(),
            Forms\Components\Select::make('assigned_user_id')->label('Aģents')
                ->relationship('assignedTo', 'name', modifyQueryUsing: fn (EloquentBuilder $query) => $query->assignable())->required()->searchable()->preload()->optionsLimit(20),
            Forms\Components\Select::make('izpilditajs_id')->label('Izpildītājs')
                ->relationship('izpilditajs', 'name')
                ->getOptionLabelUsing(fn ($value): ?string => Izpilditajs::find($value)?->display_label)
                ->searchable()->preload()->optionsLimit(50)
                ->columnSpanFull()
                ->nullable(),
            Forms\Components\Select::make('client_id')->label('Klients')
                ->searchable()
                ->options(fn () => Client::query()->orderBy('name')->limit(20)->pluck('name', 'id')->all())
                ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name),
            Forms\Components\Select::make('property_id')->label('Īpašums')
                ->searchable()
                ->options(fn () => CrmProperty::query()
                    ->where('status', '!=', 'dzests')
                    ->orderByDesc('id')
                    ->limit(100)
                    ->get()
                    ->mapWithKeys(fn ($p) => [$p->id => $p->selection_label])
                    ->toArray())
                ->getOptionLabelUsing(fn ($value): ?string => CrmProperty::find($value)?->selection_label),
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

            // Paziņojumi netiek sūtīti automātiski — tos izsūta tikai ar
            // pogām. Bloks redzams tikai esošam ierakstam.
            Section::make('Nosūtīt paziņojumu')
                ->columnSpanFull()
                ->visible(fn (string $operation): bool => $operation === 'edit')
                ->schema([
                    View::make('filament.forms.task-notify-buttons')
                        ->viewData(fn (): array => [
                            'sendUrlAgent' => $schema->getRecord()?->getKey()
                                ? route('tasks.notify.agent', ['id' => $schema->getRecord()->getKey()])
                                : null,
                            'sendUrlIzpilditajs' => $schema->getRecord()?->getKey()
                                ? route('tasks.notify.izpilditajs', ['id' => $schema->getRecord()->getKey()])
                                : null,
                            'agentName' => $schema->getRecord()?->assignedTo?->name,
                            'agentEmail' => $schema->getRecord()?->assignedTo?->email,
                            'izpilditajsName' => $schema->getRecord()?->izpilditajs?->name,
                            'izpilditajsEmail' => $schema->getRecord()?->izpilditajs?->email,
                            // Saglabātais "Nosūtīts" statuss (ne tikai pēc POST).
                            'agentNotifiedAt' => $schema->getRecord()?->agent_notified_at,
                            'izpilditajsNotifiedAt' => $schema->getRecord()?->izpilditajs_notified_at,
                        ])
                        ->columnSpanFull(),
                ]),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('completed')
                    ->label('Izpildīts')
                    ->boolean()
                    ->getStateUsing(fn ($record) => (bool) $record->completed_at)
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-check-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    // The global "all table icons are primary" CSS rule would
                    // paint the gray checkmark teal; this class lets the
                    // uncompleted state keep a muted gray.
                    ->extraCellAttributes(fn ($record): array => ['class' => $record->completed_at ? 'pdc-task-completed' : 'pdc-task-uncompleted'])
                    ->tooltip(fn ($record) => $record->completed_at ? 'Izpildīts' : null)
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('completed_at', $direction)),
                Tables\Columns\TextColumn::make('title')->label('Uzdevums')->searchable()->sortable()->weight('bold')->wrap()
                    ->url(fn ($record) => route('filament.admin.resources.tasks.edit', $record)),
                Tables\Columns\TextColumn::make('due_at')->label('Līdz')->dateTime('d.m.Y H:i')->sortable()->extraCellAttributes(['class' => 'pdc-nowrap'])
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null)
                    ->icon(fn ($record) => $record->isOverdue() ? 'heroicon-o-exclamation-triangle' : null)
                    ->iconColor('warning'),
                Tables\Columns\TextColumn::make('assignedTo.name')->label('Aģents')->sortable()
                    ->url(fn ($record) => $record->assigned_user_id ? route('filament.admin.resources.users.edit', $record->assigned_user_id) : null),
                Tables\Columns\TextColumn::make('izpilditajs.name')->label('Izpildītājs')->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('client.name')->label('Klients')->sortable()
                    ->url(fn ($record) => $record->client_id ? route('filament.admin.resources.clients.view', $record->client_id) : null),
                Tables\Columns\TextColumn::make('property.selection_label')->label('Īpašums')->wrap()
                    ->placeholder('—')
                    // selection_label is a computed accessor — sort by the
                    // underlying property title via subquery.
                    ->sortable(query: fn ($query, $direction) => $query->orderBy(
                        CrmProperty::query()->select('title')->whereColumn('crm_properties.id', 'tasks.property_id'),
                        $direction,
                    ))
                    ->url(fn ($record) => $record->property ? route('filament.admin.resources.properties.view', $record->property) : null),
            ])
            ->filters([
                Tables\Filters\Filter::make('open')->label('Atvērti')->query(fn ($query) => $query->whereNull('completed_at')),
                Tables\Filters\Filter::make('overdue')->label('Nokavēti')->query(
                    fn ($query) => $query->whereNull('completed_at')->where('due_at', '<', now())
                ),
                Tables\Filters\Filter::make('today')->label('Šodien')->query(
                    fn ($query) => $query->whereNull('completed_at')->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])
                ),
                Tables\Filters\Filter::make('assigned')->label('Mani uzdevumi')->query(
                    fn ($query) => $query->where('assigned_user_id', auth()->id())
                ),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\Action::make('complete')
                        ->label('Pabeigt')
                        ->icon('heroicon-o-check')
                        ->visible(fn ($record) => ! $record->completed_at)
                        ->action(fn ($record) => $record->update(['completed_at' => now()])),
                    Actions\Action::make('reopen')
                        ->label('Atzīmēt kā neizpildītu')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->visible(fn ($record) => (bool) $record->completed_at)
                        ->action(fn ($record) => $record->update(['completed_at' => null])),
                    Actions\EditAction::make()->label('Rediģēt')->color('gray'),
                    Actions\DeleteAction::make()->label('Dzēst')->color('danger'),
                ])->color('gray'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('complete')
                        ->label('Pabeigt')
                        ->icon('heroicon-o-check')
                        ->color('gray')
                        ->action(function (Collection $records): void {
                            $records->each(fn (Task $record) => $record->whereKey($record->getKey())
                                ->whereNull('completed_at')
                                ->update(['completed_at' => now()]));

                            Notification::make()
                                ->title($records->count().' uzdevumi atzīmēti kā izpildīti')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Actions\BulkAction::make('reopen')
                        ->label('Atzīmēt kā neizpildītu')
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->color('gray')
                        ->action(function (Collection $records): void {
                            $records->each(fn (Task $record) => $record->whereKey($record->getKey())
                                ->update(['completed_at' => null]));

                            Notification::make()
                                ->title($records->count().' uzdevumi atzīmēti kā neizpildīti')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Actions\DeleteBulkAction::make()->label('Dzēst')->color('danger'),
                ]),
            ])
            ->defaultSort('due_at')
            ->poll('60s');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Task::query()->whereNull('completed_at')
            ->when(! auth()->user()?->can('manage'), fn ($q) => $q->where('assigned_user_id', auth()->id()))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }
}
