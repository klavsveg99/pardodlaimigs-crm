<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WpformEntryResource\Pages;
use App\Models\WpformEntry;
use Filament\Actions;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
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
    ];

    public const EDITABLE_STATUSES = [
        'new' => 'Jauns',
        'review' => 'Izvērtēts',
        'replied' => 'Atbildēts',
        'spam' => 'Mēstule',
        'archived' => 'Arhivēts',
    ];

    protected static ?string $model = WpformEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $navigationLabel = 'Pieteikumi';

    protected static string|UnitEnum|null $navigationGroup = 'Avots';

    protected static ?string $modelLabel = 'Formas ieraksts';

    protected static ?string $pluralModelLabel = 'Pieteikumi';

    protected static ?int $navigationSort = 12;

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->orderByDesc('created_at'))
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('Iesniegts')->dateTime('d.m.Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('form_name')->label('Forma')->badge()->color('info'),
                Tables\Columns\TextColumn::make('email')->label('E-pasts')
                    ->getStateUsing(fn (WpformEntry $record) => $record->fieldValue('E-pasts'))
                    ->searchable(query: fn ($query, $search) => $query->where('fields', 'like', '%E-pasts%')->where('fields', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('name')->label('Vārds')
                    ->getStateUsing(fn (WpformEntry $record) => $record->fieldValue('Jūsu vārds'))
                    ->wrap()
                    ->limit(30)
                    ->searchable(query: fn ($query, $search) => $query->where('fields', 'like', '%Jūsu vārds%')->where('fields', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('phone')->label('Tālrunis')
                    ->getStateUsing(fn (WpformEntry $record) => $record->fieldValue('Telefona numurs'))
                    ->searchable(query: fn ($query, $search) => $query->where('fields', 'like', '%Telefona numurs%')->where('fields', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('status')->label('Statuss')
                    ->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state ?? '—')
                    ->color(fn ($state) => match ($state) {
                        'new' => 'info',
                        'review' => 'warning',
                        'replied' => 'success',
                        'spam' => 'danger',
                        'archived' => 'gray',
                        'klients_pievienots' => 'success',
                        default => 'gray',
                    }),
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
                Actions\Action::make('change_status')
                    ->label('Mainīt statusu')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Select::make('status')
                            ->label('Statuss')
                            ->options(self::EDITABLE_STATUSES)
                            ->required(),
                    ])
                    ->action(function (WpformEntry $record, array $data): void {
                        $record->update(['status' => $data['status']]);
                        Notification::make()
                            ->title('Statuss mainīts')
                            ->success()
                            ->send();
                    })
                    ->after(fn () => $this->refreshPage()),
                ViewAction::make()->label('Skatīt'),
                DeleteAction::make()->label('Dzēst'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label('Dzēst izvēlētos'),
                ]),
            ])
            ->paginated([25, 50, 100])
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
