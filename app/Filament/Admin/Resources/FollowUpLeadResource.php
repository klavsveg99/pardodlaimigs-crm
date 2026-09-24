<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\FollowUpLeadResource\Pages;
use App\Models\FollowUpLead;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use UnitEnum;

/**
 * "Follow Up līdi" — potenciālie pārdevēji, kuri vēl nav gatavi sadarbībai.
 * Pirms-sadarbības stadija: CRM atgādina aģentam regulāri sazināties, līdz
 * līdis tiek aizvērts ("Sadarbība uzsākta" / "Pārtraukts").
 */
class FollowUpLeadResource extends Resource
{
    protected static ?string $model = FollowUpLead::class;

    protected static ?string $slug = 'follow-up-leads';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-phone-arrow-up-right';

    protected static ?string $navigationLabel = 'Follow Up līdi';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $modelLabel = 'Follow Up līdis';

    protected static ?string $pluralModelLabel = 'Follow Up līdi';

    protected static ?int $navigationSort = 25;

    public static function canAccess(): bool
    {
        return ! auth()->user()?->isPhoto();
    }

    // Aģenti redz tikai savus līdus — arī atverot tiešu URL.
    public static function getEloquentQuery(): EloquentBuilder
    {
        return parent::getEloquentQuery()->when(
            ! auth()->user()?->can('manage'),
            fn ($query) => $query->where('owner_user_id', auth()->id()),
        );
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Līdis')->columns(['default' => 1, 'md' => 2])->schema([
                Forms\Components\Select::make('client_id')
                    ->label('Klients')
                    ->relationship('client', 'name')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(20)
                    ->required(),

                Forms\Components\Select::make('crm_property_id')
                    ->label('Saistītais īpašums')
                    ->relationship('property', 'title')
                    ->searchable()
                    ->preload()
                    ->optionsLimit(20)
                    ->placeholder('Nav piesaistīts'),

                Forms\Components\TextInput::make('property_address')
                    ->label('Īpašuma adrese')
                    ->maxLength(255)
                    ->placeholder('Ja īpašums vēl nav CRM')
                    ->columnSpanFull(),

                Forms\Components\DatePicker::make('conversation_started_at')
                    ->label('Sarunas sākums')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->default(fn (): string => today()->toDateString()),

                Forms\Components\Select::make('reason')
                    ->label('Iemesls, kāpēc nevar sākt sadarbību')
                    ->options(FollowUpLead::REASONS)
                    ->placeholder('Nav norādīts'),

                Forms\Components\Textarea::make('reason_note')
                    ->label('Iemesla piezīme')
                    ->rows(2)
                    ->columnSpanFull(),

                Forms\Components\DatePicker::make('target_start_at')
                    ->label('Plānotais sadarbības sākums')
                    ->native(false)
                    ->displayFormat('d.m.Y'),

                Forms\Components\Select::make('cadence_days')
                    ->label('Atgādinājuma intervāls')
                    ->options(FollowUpLead::CADENCES)
                    ->default(7)
                    ->required(),

                Forms\Components\DatePicker::make('next_contact_at')
                    ->label('Nākamais kontakts')
                    ->native(false)
                    ->displayFormat('d.m.Y')
                    ->default(fn (): string => today()->addDays(7)->toDateString())
                    ->required(),

                Forms\Components\Select::make('status')
                    ->label('Statuss')
                    ->options(FollowUpLead::STATUSES)
                    ->default('active')
                    ->required(),

                Forms\Components\Select::make('owner_user_id')
                    ->label('Atbildīgais aģents')
                    ->relationship('owner', 'name', modifyQueryUsing: fn (EloquentBuilder $query) => $query->assignable())
                    ->searchable()
                    ->preload()
                    ->optionsLimit(20)
                    ->default(fn (): ?int => auth()->id())
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('notes')
                    ->label('Piezīmes')
                    ->rows(4)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.name')->label('Klients')->searchable()->sortable()->weight('bold')
                    ->description(fn (FollowUpLead $record): ?string => $record->client?->phone)
                    ->url(fn (FollowUpLead $record): ?string => $record->client
                        ? ClientResource::getUrl('view', ['record' => $record->client])
                        : null),
                Tables\Columns\TextColumn::make('property.title')->label('Īpašums')
                    ->getStateUsing(fn (FollowUpLead $record): ?string => $record->property?->title ?? $record->property_address)
                    ->placeholder('—')
                    ->wrap()
                    ->limit(45),
                Tables\Columns\TextColumn::make('reason')->label('Iemesls')->badge()->color('gray')->placeholder('—'),
                Tables\Columns\TextColumn::make('next_contact_at')->label('Nākamais kontakts')->date('d.m.Y')->sortable()
                    ->extraCellAttributes(['class' => 'pdc-nowrap'])
                    ->color(fn (FollowUpLead $record): ?string => $record->isOverdue() ? 'danger' : null)
                    ->icon(fn (FollowUpLead $record): ?string => $record->isOverdue() ? 'heroicon-o-exclamation-triangle' : null)
                    ->iconColor('warning'),
                Tables\Columns\TextColumn::make('owner.name')->label('Aģents')->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('contact_count')->label('Kontakti')->sortable()->alignCenter(),
                Tables\Columns\TextColumn::make('status')->label('Statuss')->badge()->sortable()
                    ->formatStateUsing(fn ($state): string => FollowUpLead::STATUSES[$state] ?? (string) $state)
                    ->color(fn ($state): string => match ($state) {
                        'active' => 'info',
                        'closed' => 'success',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('next_contact_at')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->label('Statuss')
                    ->options(FollowUpLead::STATUSES)
                    ->preload(),
                Tables\Filters\SelectFilter::make('reason')->label('Iemesls')
                    ->options(FollowUpLead::REASONS)
                    ->preload(),
                Tables\Filters\SelectFilter::make('owner_user_id')->label('Aģents')
                    ->options(fn (): array => User::query()->assignable()->orderBy('name')->pluck('name', 'id')->all())
                    ->visible(fn (): bool => auth()->user()?->can('manage') ?? false)
                    ->searchable(),
                Tables\Filters\Filter::make('overdue')->label('Nokavētie')
                    ->query(fn ($query) => $query->where('status', 'active')->whereDate('next_contact_at', '<', today())),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->filtersFormColumns(4)
            ->actions([
                Actions\ActionGroup::make([
                    Actions\Action::make('contacted')
                        ->label('Sazinājos')
                        ->icon('heroicon-o-phone')
                        ->color('primary')
                        ->visible(fn (FollowUpLead $record): bool => $record->status === 'active')
                        ->form([
                            Forms\Components\Textarea::make('note')
                                ->label('Kontakta piezīme (neobligāta)')
                                ->rows(2),
                        ])
                        ->modalHeading('Atzīmēt kontaktu')
                        ->modalDescription('Kontakts tiks pierakstīts un nākamais atgādinājums ieplānots pēc izvēlētā intervāla.')
                        ->modalSubmitActionLabel('Saglabāt')
                        ->action(function (FollowUpLead $record, array $data): void {
                            $record->markContacted($data['note'] ?? null);

                            Notification::make()
                                ->title('Kontakts pievienots')
                                ->body('Nākamais kontakts: '.$record->fresh()->next_contact_at?->format('d.m.Y'))
                                ->success()
                                ->send();
                        }),
                    Actions\Action::make('close')
                        ->label('Sadarbība uzsākta')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (FollowUpLead $record): bool => $record->status === 'active')
                        ->requiresConfirmation()
                        ->modalHeading('Atzīmēt, ka sadarbība ir uzsākta?')
                        ->modalDescription('Atgādinājumi tiks pārtraukti. Līdis paliks vēsturē.')
                        ->modalSubmitActionLabel('Apstiprināt')
                        ->action(function (FollowUpLead $record): void {
                            $record->update(['status' => 'closed']);
                            Notification::make()->title('Sadarbība atzīmēta kā uzsākta')->success()->send();
                        }),
                    Actions\Action::make('drop')
                        ->label('Pārtraukts')
                        ->icon('heroicon-o-x-circle')
                        ->color('gray')
                        ->visible(fn (FollowUpLead $record): bool => $record->status === 'active')
                        ->requiresConfirmation()
                        ->modalHeading('Pārtraukt Follow Up?')
                        ->modalDescription('Līdis netiks dzēsts, bet atgādinājumi apstāsies.')
                        ->modalSubmitActionLabel('Pārtraukt')
                        ->action(function (FollowUpLead $record): void {
                            $record->update(['status' => 'dropped']);
                            Notification::make()->title('Follow Up pārtraukts')->success()->send();
                        }),
                    Actions\Action::make('reopen')
                        ->label('Atjaunot')
                        ->icon('heroicon-o-arrow-path')
                        ->color('gray')
                        ->visible(fn (FollowUpLead $record): bool => $record->status !== 'active')
                        ->action(function (FollowUpLead $record): void {
                            $record->update([
                                'status' => 'active',
                                'next_contact_at' => today()->addDays(max(1, (int) ($record->cadence_days ?: 7))),
                            ]);
                            Notification::make()->title('Follow Up atjaunots')->success()->send();
                        }),
                    Actions\EditAction::make()->label('Rediģēt')->color('gray'),
                    Actions\DeleteAction::make()->label('Dzēst')->color('danger'),
                ])->color('gray'),
            ])
            ->poll('60s');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = FollowUpLead::query()
            ->due()
            ->when(! auth()->user()?->can('manage'), fn ($q) => $q->where('owner_user_id', auth()->id()))
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getNavigationBadge() ? 'warning' : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFollowUpLeads::route('/'),
            'create' => Pages\CreateFollowUpLead::route('/create'),
            'edit' => Pages\EditFollowUpLead::route('/{record}/edit'),
        ];
    }
}
