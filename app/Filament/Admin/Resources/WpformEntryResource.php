<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WpformEntryResource\Pages;
use App\Models\WpformEntry;
use App\Support\PhoneFormat;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use UnitEnum;

class WpformEntryResource extends Resource
{
    public const STATUSES = [
        'new' => 'Jauns',
        'review' => 'Izvērtēts',
        'replied' => 'Atbildēts',
        'spam' => 'Mēstule',
        'archived' => 'Arhivēts',
        'klients_pievienots' => 'Klients pievienots',
        'deleted' => 'Dzēsts',
    ];

    public const STATUS_COLORS = [
        'new' => 'info',
        'review' => 'warning',
        'replied' => 'success',
        'spam' => 'danger',
        'archived' => 'gray',
        'klients_pievienots' => 'success',
        'deleted' => 'gray',
    ];

    // "deleted" is reached only through the trash action, which also writes
    // the WordPress re-sync tombstone. Never expose it as a plain status
    // choice — a raw status update would leave the entry resurrectable.
    public const SELECTABLE_STATUSES = [
        'new' => 'Jauns',
        'review' => 'Izvērtēts',
        'replied' => 'Atbildēts',
        'spam' => 'Mēstule',
        'archived' => 'Arhivēts',
        'klients_pievienots' => 'Klients pievienots',
    ];

    protected static ?string $model = WpformEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Pieteikumi';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $modelLabel = 'Formas ieraksts';

    protected static ?string $pluralModelLabel = 'Pieteikumi';

    protected static ?int $navigationSort = 30;

    // Pieteikumi ir konfidenciāli — pieejami tikai administratoriem
    // (slēpti arī navigācijā un klienta skatā).
    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function table(Table $table): Table
    {
        // Bulk deletion depends on the active tab: on "Dzēstie" the rows are
        // already soft-deleted, so the only meaningful mass action is a
        // permanent delete (soft-deleting them again would do nothing).
        $trashSelected = BulkAction::make('trash_selected')
            ->label('Dzēst izvēlētos')
            ->icon('heroicon-o-trash')
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Pārvietot izvēlētos pieteikumus uz "Dzēstie"?')
            ->modalDescription('Ieraksti paliks sadaļā "Dzēstie" un būs atjaunojami.')
            ->modalSubmitActionLabel('Dzēst')
            ->action(function (Collection $records): void {
                $moved = 0;

                foreach ($records as $record) {
                    if ($record->status !== 'deleted') {
                        $record->moveToTrash();
                        $moved++;
                    }
                }

                Notification::make()
                    ->title($moved.' pieteikumi pārvietoti uz "Dzēstie"')
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
        $trashSelected->visible(fn (): bool => ($trashSelected->getLivewire()?->activeTab ?? 'active') !== 'deleted');

        $forceDeleteSelected = BulkAction::make('force_delete_selected')
            ->label('Izdzēst neatgriezeniski')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Vai tiešām neatgriezeniski dzēst izvēlētos pieteikumus?')
            ->modalDescription('Ieraksti tiks pilnībā izņemti no CRM, un tos vairs nevarēs atjaunot.')
            ->modalSubmitActionLabel('Izdzēst neatgriezeniski')
            ->action(function (Collection $records): void {
                $deleted = 0;

                foreach ($records as $record) {
                    if ($record->status === 'deleted') {
                        $record->delete();
                        $deleted++;
                    }
                }

                Notification::make()
                    ->title($deleted.' pieteikumi neatgriezeniski izdzēsti')
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
        $forceDeleteSelected->visible(fn (): bool => ($forceDeleteSelected->getLivewire()?->activeTab ?? 'active') === 'deleted'
            && (auth()->user()?->can('manage') ?? false));

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Iesniegts')->dateTime('d.m.Y H:i')->sortable()->extraCellAttributes(['class' => 'pdc-nowrap']),
                Tables\Columns\TextColumn::make('form_name')->label('Forma')->badge()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('E-pasts')
                    ->getStateUsing(fn (WpformEntry $record) => $record->fieldValue('E-pasts'))
                    // fields is a JSON array of {name, value} objects — sort
                    // via json_each lookup, not a simple key path.
                    ->sortable(query: fn ($query, $direction) => $query->orderByRaw(
                        "(SELECT json_extract(je.value, '$.value') FROM json_each(COALESCE(fields, '[]')) je WHERE json_extract(je.value, '$.name') = ? LIMIT 1) ".($direction === 'desc' ? 'DESC' : 'ASC'),
                        ['E-pasts'],
                    ))
                    ->searchable(query: fn ($query, $search) => $query->where('fields', 'like', '%E-pasts%')->where('fields', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('name')->label('Vārds')
                    ->getStateUsing(fn (WpformEntry $record) => $record->fieldValue('Jūsu vārds'))
                    ->sortable(query: fn ($query, $direction) => $query->orderByRaw(
                        "(SELECT json_extract(je.value, '$.value') FROM json_each(COALESCE(fields, '[]')) je WHERE json_extract(je.value, '$.name') = ? LIMIT 1) ".($direction === 'desc' ? 'DESC' : 'ASC'),
                        ['Jūsu vārds'],
                    ))
                    ->wrap()
                    ->limit(30)
                    ->searchable(query: fn ($query, $search) => $query->where('fields', 'like', '%Jūsu vārds%')->where('fields', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('phone')->label('Tālrunis')->formatStateUsing(fn ($state) => PhoneFormat::display((string) $state))
                    ->getStateUsing(fn (WpformEntry $record) => $record->fieldValue('Telefona numurs'))
                    ->sortable(query: fn ($query, $direction) => $query->orderByRaw(
                        "(SELECT json_extract(je.value, '$.value') FROM json_each(COALESCE(fields, '[]')) je WHERE json_extract(je.value, '$.name') = ? LIMIT 1) ".($direction === 'desc' ? 'DESC' : 'ASC'),
                        ['Telefona numurs'],
                    ))
                    ->searchable(query: fn ($query, $search) => $query->where('fields', 'like', '%Telefona numurs%')->where('fields', 'like', "%{$search}%")),
                Tables\Columns\SelectColumn::make('status')->label('Statuss')
                    ->options(self::STATUSES)
                    // Deleted entries are restored via the row action (which
                    // also clears the sync tombstone), never inline.
                    ->disabled(fn (WpformEntry $record): bool => $record->status === 'deleted')
                    // Choosing "Dzēsts" inline must run the same trash flow as
                    // the row action (moving status + writing the WordPress
                    // re-sync tombstone); a raw update would be resurrectable.
                    ->updateStateUsing(function (WpformEntry $record, mixed $state): string {
                        if ($state === 'deleted') {
                            $record->moveToTrash();
                        } else {
                            $record->update(['status' => $state]);
                        }

                        return (string) $record->status;
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Statuss')
                    ->options(self::STATUSES),
                Tables\Filters\Filter::make('linked_client')->label('Piesaistīts klientam')
                    ->query(fn ($q) => $q->whereNotNull('client_id')),
                Tables\Filters\Filter::make('unlinked')->label('Bez klienta')
                    ->query(fn ($q) => $q->whereNull('client_id')),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()->label('Skatīt')->color('gray'),
                    Action::make('trash')
                        ->label('Dzēst')
                        ->icon('heroicon-o-trash')
                        ->color('gray')
                        ->visible(fn (WpformEntry $record): bool => $record->status !== 'deleted')
                        ->requiresConfirmation()
                        ->modalHeading('Pārvietot pieteikumu uz "Dzēstie"?')
                        ->modalDescription('Ieraksts pazudīs no aktīvā saraksta, bet paliks sadaļā "Dzēstie" un būs atjaunojams.')
                        ->modalSubmitActionLabel('Dzēst')
                        ->action(function (WpformEntry $record): void {
                            $record->moveToTrash();

                            Notification::make()
                                ->title('Pieteikums pārvietots uz "Dzēstie"')
                                ->success()
                                ->send();
                        }),
                    Action::make('restore')
                        ->label('Atjaunot')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->visible(fn (WpformEntry $record): bool => $record->status === 'deleted')
                        ->requiresConfirmation()
                        ->modalHeading('Atjaunot pieteikumu?')
                        ->modalDescription('Ieraksts atgriezīsies aktīvajā sarakstā.')
                        ->modalSubmitActionLabel('Atjaunot')
                        ->action(function (WpformEntry $record): void {
                            $record->restoreFromTrash();

                            Notification::make()
                                ->title('Pieteikums atjaunots')
                                ->success()
                                ->send();
                        }),
                    Action::make('force_delete')
                        ->label('Izdzēst neatgriezeniski')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->visible(fn (WpformEntry $record): bool => $record->status === 'deleted'
                            && (auth()->user()?->can('manage') ?? false))
                        ->requiresConfirmation()
                        ->modalHeading('Vai tiešām neatgriezeniski dzēst šo pieteikumu?')
                        ->modalDescription('Ieraksts tiks pilnībā izņemts no CRM, un to vairs nevarēs atjaunot.')
                        ->modalSubmitActionLabel('Izdzēst neatgriezeniski')
                        ->action(function (WpformEntry $record): void {
                            // The `deleted` hook keeps the sync tombstone, so
                            // WordPress can never re-import it afterwards.
                            $record->delete();

                            Notification::make()
                                ->title('Pieteikums neatgriezeniski izdzēsts')
                                ->success()
                                ->send();
                        }),
                ])->color('gray'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    $trashSelected,
                    $forceDeleteSelected,
                ]),
            ])
            ->paginated([25, 50, 100])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = WpformEntry::where('status', 'new')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() ? 'primary' : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWpformEntries::route('/'),
            'view' => Pages\ViewWpformEntry::route('/{record}'),
        ];
    }
}
