<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TaskResource\Pages;
use App\Models\Client;
use App\Models\Deal;
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

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('title')->label('Nosaukums')->required()->columnSpanFull(),
            Forms\Components\Textarea::make('body')->label('Apraksts')->rows(3)->columnSpanFull(),
            Forms\Components\DateTimePicker::make('due_at')->label('Līdz')->native(false)->required(),
            Forms\Components\Select::make('assigned_user_id')->label('Aģents')
                ->relationship('assignedTo', 'name')->searchable()->preload()->optionsLimit(20),
            Forms\Components\Select::make('client_id')->label('Klients')
                ->searchable()
                ->options(fn () => Client::query()->orderBy('name')->limit(20)->pluck('name', 'id')->all())
                ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name),
            Forms\Components\Select::make('deal_id')->label('Darījums')
                ->searchable()
                ->options(function () {
                    return Deal::query()
                        ->with(['client', 'property'])
                        ->whereNotNull('property_id')
                        ->orderByDesc('id')
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(function ($d) {
                            $client = $d->client?->name ?? '—';
                            $property = $d->property?->selection_label ?? '—';
                            $stage = Deal::STAGES[$d->stage] ?? $d->stage;

                            return [$d->id => "#{$d->id} · {$client} · {$property} · {$stage}"];
                        })
                        ->toArray();
                })
                ->getSearchResultsUsing(function (string $search) {
                    return Deal::query()
                        ->with(['client', 'property'])
                        ->whereNotNull('property_id')
                        ->where(function ($q) use ($search) {
                            $q->where('id', 'like', "%{$search}%")
                                ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                                ->orWhereHas('property', fn ($p) => $p->where('title', 'like', "%{$search}%")->orWhere('kadastra_nr', 'like', "%{$search}%"));
                        })
                        ->orderByDesc('id')
                        ->limit(20)
                        ->get()
                        ->mapWithKeys(function ($d) {
                            $client = $d->client?->name ?? '—';
                            $property = $d->property?->selection_label ?? '—';
                            $stage = Deal::STAGES[$d->stage] ?? $d->stage;

                            return [$d->id => "#{$d->id} · {$client} · {$property} · {$stage}"];
                        })
                        ->toArray();
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    $d = Deal::with(['client', 'property'])->find($value);
                    if (! $d) {
                        return null;
                    }
                    $client = $d->client?->name ?? '—';
                    $property = $d->property?->selection_label ?? '—';
                    $stage = Deal::STAGES[$d->stage] ?? $d->stage;

                    return "#{$d->id} · {$client} · {$property} · {$stage}";
                }),
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
                Tables\Columns\IconColumn::make('completed_at')
                    ->label('')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
                Tables\Columns\TextColumn::make('title')->label('Uzdevums')->searchable()->weight('bold')->wrap(),
                Tables\Columns\TextColumn::make('due_at')->label('Līdz')->dateTime('d.m.Y H:i')->sortable()->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('assignedTo.name')->label('Kam'),
                Tables\Columns\TextColumn::make('client.name')->label('Klients'),
                Tables\Columns\IconColumn::make('is_overdue')
                    ->label('Nokavēts')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isOverdue())
                    ->trueColor('danger')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\Filter::make('open')->label('Atvērti')->query(fn ($query) => $query->whereNull('completed_at')),
                Tables\Filters\Filter::make('overdue')->label('Nokavēti')->query(
                    fn ($query) => $query->whereNull('completed_at')->where('due_at', '<', now())
                ),
                Tables\Filters\Filter::make('today')->label('Šodien')->query(
                    fn ($query) => $query->whereNull('completed_at')->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])
                ),
            ])
            ->actions([
                Actions\Action::make('complete')
                    ->label('Pabeigt')
                    ->icon('heroicon-o-check')
                    ->visible(fn ($record) => ! $record->completed_at)
                    ->action(fn ($record) => $record->update(['completed_at' => now()])),
                Actions\EditAction::make(),
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
