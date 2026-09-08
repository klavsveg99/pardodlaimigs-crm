<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TaskResource\Pages;
use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\Izpilditajs;
use App\Models\Task;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Uzdevumi';

    protected static string|UnitEnum|null $navigationGroup = 'Darbplūsma';

    protected static ?string $modelLabel = 'Uzdevums';

    protected static ?string $pluralModelLabel = 'Uzdevumi';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('title')->label('Nosaukums')->required()->columnSpanFull(),
            Forms\Components\Textarea::make('body')->label('Apraksts')->rows(3)->columnSpanFull(),
            Forms\Components\DateTimePicker::make('due_at')->label('Līdz')->native(false)->required()->minDate(now()),
            Forms\Components\Select::make('assigned_user_id')->label('Aģents')
                ->relationship('assignedTo', 'name')->required()->searchable()->preload()->optionsLimit(20),
            Forms\Components\Select::make('izpilditajs_id')->label('Izpildītājs')
                ->relationship('izpilditajs', 'name')
                ->getOptionLabelUsing(fn ($value): ?string => Izpilditajs::find($value)?->display_label)
                ->searchable()->preload()->optionsLimit(50)
                ->helperText('Ja nepieciešams, izveido jaunu zem Sistēma > Izpildītāji')
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
            ->columns([
                Tables\Columns\IconColumn::make('is_overdue')
                    ->label('')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isOverdue())
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon(false)
                    ->trueColor('warning')
                    ->tooltip(fn ($record) => $record->isOverdue() ? 'Nokavēts' : null)
                    ->sortable(query: fn ($query, $direction) => $query->orderBy('due_at', $direction)),
                Tables\Columns\TextColumn::make('title')->label('Uzdevums')->searchable()->sortable()->weight('bold')->wrap()
                    ->url(fn ($record) => route('filament.admin.resources.tasks.edit', $record)),
                Tables\Columns\TextColumn::make('due_at')->label('Līdz')->dateTime('d.m.Y H:i')->sortable()->extraCellAttributes(['class' => 'pdc-nowrap'])
                    ->color(fn ($record) => $record->isOverdue() ? 'danger' : null)
                    ->icon(fn ($record) => $record->isOverdue() ? 'heroicon-o-exclamation-triangle' : null)
                    ->iconColor('danger'),
                Tables\Columns\TextColumn::make('assignedTo.name')->label('Aģents')->sortable()
                    ->url(fn ($record) => $record->assigned_user_id ? route('filament.admin.resources.users.edit', $record->assigned_user_id) : null),
                Tables\Columns\TextColumn::make('izpilditajs.name')->label('Izpildītājs')->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('client.name')->label('Klients')->sortable()
                    ->url(fn ($record) => $record->client_id ? route('filament.admin.resources.clients.view', $record->client_id) : null),
                Tables\Columns\TextColumn::make('property.selection_label')->label('Īpašums')->wrap()
                    ->placeholder('—')
                    ->url(fn ($record) => $record->property_id ? route('filament.admin.resources.crm-properties.view', $record->property_id) : null),
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
                    Actions\EditAction::make()->label('Rediģēt')->color('gray'),
                ])->color('gray'),
            ])
            ->defaultSort('due_at')
            ->poll('60s');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Task::whereNull('completed_at')->count();

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
