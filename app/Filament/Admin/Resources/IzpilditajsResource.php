<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\IzpilditajsResource\Pages;
use App\Filament\Forms\Components\PhoneInput;
use App\Models\Izpilditajs;
use App\Rules\Phone;
use App\Support\PhoneFormat;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class IzpilditajsResource extends Resource
{
    protected static ?string $model = Izpilditajs::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Izpildītāji';

    protected static string|UnitEnum|null $navigationGroup = 'Sistēma';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Izpildītājs';

    protected static ?string $pluralModelLabel = 'Izpildītāji';

    protected static ?string $recordRouteKeyName = 'slug';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Pamatdati')->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Vārds / uzņēmums')
                    ->required()
                    ->maxLength(200),
                Forms\Components\Select::make('category')
                    ->label('Kategorija')
                    ->required()
                    ->searchable()
                    ->options(Izpilditajs::CATEGORIES)
                    ->native(false),
                Forms\Components\TextInput::make('email')
                    ->label('E-pasts')
                    ->required()
                    ->email()
                    ->maxLength(255),
                PhoneInput::make('phone')
                    ->label('Tālrunis')
                    ->maxLength(20)
                    ->rule(new Phone),
                Forms\Components\Textarea::make('notes_md')
                    ->label('Piezīmes')
                    ->rows(4)
                    ->columnSpanFull(),
            ])->columns(2),

            // Only meaningful once the izpildītājs exists (nothing to list on
            // the create page).
            Section::make('Saistītie uzdevumi')
                ->columnSpanFull()
                ->visible(fn (string $operation): bool => $operation !== 'create')
                ->schema([
                    View::make('filament.forms.izpilditajs-tasks-preview')
                        ->columnSpanFull(),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        // Bulk delete depends on the active tab: on "Dzēstie" the rows are
        // already trashed, so only a permanent delete makes sense.
        $trashSelected = Actions\DeleteBulkAction::make('trash_selected')
            ->label('Dzēst')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Pārvietot izvēlētos izpildītājus uz "Dzēstie"?')
            ->modalDescription('Izpildītāji paliks sadaļā "Dzēstie" un būs atjaunojami.')
            ->modalSubmitActionLabel('Dzēst')
            ->deselectRecordsAfterCompletion();
        $trashSelected->visible(fn (): bool => ($trashSelected->getLivewire()?->activeTab ?? 'active') !== 'deleted');

        $forceDeleteSelected = Actions\ForceDeleteBulkAction::make('force_delete_selected')
            ->label('Izdzēst neatgriezeniski')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Vai tiešām neatgriezeniski dzēst izvēlētos izpildītājus?')
            ->modalDescription('Ieraksti tiks pilnībā izņemti no CRM, un tos vairs nevarēs atjaunot.')
            ->modalSubmitActionLabel('Izdzēst neatgriezeniski')
            ->deselectRecordsAfterCompletion();
        $forceDeleteSelected->visible(fn (): bool => ($forceDeleteSelected->getLivewire()?->activeTab ?? 'active') === 'deleted'
            && (auth()->user()?->can('manage') ?? false));

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable()->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('name')->label('Vārds')->searchable()->sortable()->weight('bold')->wrap(),
                Tables\Columns\TextColumn::make('category')->label('Kategorija')->badge()->searchable()->sortable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('email')->label('E-pasts')->searchable()->copyable()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('phone')->label('Tālrunis')->sortable()->placeholder('—')->toggleable()->formatStateUsing(fn ($state) => PhoneFormat::display((string) $state)),
                Tables\Columns\TextColumn::make('tasks_count')
                    ->label('Uzdevumu')
                    ->counts('tasks')
                    ->sortable()
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atjaunināts')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->extraCellAttributes(['class' => 'pdc-nowrap']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategorija')
                    ->options(Izpilditajs::CATEGORIES),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()->label('Skatīt')->color('gray')
                        ->visible(fn (Izpilditajs $record): bool => ! $record->trashed()),
                    Actions\EditAction::make()->label('Rediģēt')->color('gray')
                        ->visible(fn (Izpilditajs $record): bool => ! $record->trashed()),
                    Actions\DeleteAction::make()->label('Dzēst')->color('gray')
                        ->visible(fn (Izpilditajs $record): bool => ! $record->trashed())
                        ->modalHeading('Pārvietot izpildītāju uz "Dzēstie"?')
                        ->modalDescription('Izpildītājs pazudīs no aktīvā saraksta, bet paliks sadaļā "Dzēstie" un būs atjaunojams.')
                        ->modalSubmitActionLabel('Dzēst'),
                    Actions\RestoreAction::make()->label('Atjaunot')->color('gray')
                        ->visible(fn (Izpilditajs $record): bool => $record->trashed())
                        ->modalHeading('Atjaunot izpildītāju?')
                        ->modalDescription('Izpildītājs atgriezīsies aktīvajā sarakstā.')
                        ->modalSubmitActionLabel('Atjaunot'),
                    Actions\ForceDeleteAction::make()->label('Izdzēst neatgriezeniski')->color('danger')
                        ->visible(fn (Izpilditajs $record): bool => $record->trashed() && (auth()->user()?->can('manage') ?? false))
                        ->modalHeading('Vai tiešām neatgriezeniski dzēst šo izpildītāju?')
                        ->modalDescription('Izpildītājs tiks pilnībā izņemts no CRM. To vairs nevarēs atjaunot.')
                        ->modalSubmitActionLabel('Izdzēst neatgriezeniski'),
                ])->color('gray'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    $trashSelected,
                    $forceDeleteSelected,
                ]),
            ])
            ->recordUrl(fn (Izpilditajs $record): ?string => $record->trashed() ? null : static::getUrl('view', ['record' => $record]))
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIzpilditajs::route('/'),
            'create' => Pages\CreateIzpilditajs::route('/create'),
            'view' => Pages\ViewIzpilditajs::route('/{record}'),
            'edit' => Pages\EditIzpilditajs::route('/{record}/edit'),
        ];
    }
}
