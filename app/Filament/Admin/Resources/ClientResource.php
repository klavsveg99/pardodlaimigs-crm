<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ClientResource\Pages;
use App\Filament\Admin\Resources\ClientResource\RelationManagers;
use App\Filament\Forms\Components\PersonasKodsInput;
use App\Filament\Forms\Components\PhoneInput;
use App\Models\Client;
use App\Rules\Phone;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use UnitEnum;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Klienti';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $modelLabel = 'Klients';

    protected static ?string $pluralModelLabel = 'Klienti';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->columnSpanFull()->columns(['lg' => 2])->schema([
                Grid::make(['default' => 1, 'md' => 2])->columnSpan(1)->schema([
                    Forms\Components\TextInput::make('name')->label('Vārds, uzvārds')->required()->maxLength(255),
                    PhoneInput::make('phone')->label('Tālrunis')->maxLength(20)->rule(new Phone),
                    Forms\Components\TextInput::make('email')->label('E-pasts')->email()->maxLength(255),
                    PersonasKodsInput::make('personas_kods')
                        ->label('Personas kods')
                        ->maxLength(12)
                        ->helperText('Formāts: XXXXXX-XXXXX')
                        ->disabled(fn (string $operation) => $operation === 'view')
                        ->readonly(fn (Client $record) => Str::filled($record->personas_kods)),
                    Forms\Components\Select::make('source')
                        ->label('Avots (kā uzzināja)')
                        ->searchable()
                        ->live()
                        ->options([
                            'Tīmekļa vietne' => 'Tīmekļa vietne',
                            'Sociālie tīkli' => 'Sociālie tīkli',
                            'Facebook' => 'Facebook',
                            'Instagram' => 'Instagram',
                            'Google' => 'Google',
                            'Ieteikums' => 'Ieteikums',
                            'Sludinājums' => 'Sludinājums (ss.lv u.c.)',
                            'Atgriešanās' => 'Atgriešanās (esošs klients)',
                            'Cits' => 'Cits',
                        ])
                        ->placeholder('Izvēlieties avotu'),
                    Forms\Components\TextInput::make('source_other')
                        ->label('Avots — precizējums')
                        ->helperText('Precizējiet, kā uzzinājāt par mums')
                        ->maxLength(200)
                        ->visible(fn (callable $get): bool => $get('source') === 'Cits')
                        ->columnSpanFull(),
                    Forms\Components\Select::make('owner_user_id')
                        ->label('Atbildīgais aģents')
                        ->relationship('owner', 'name')
                        ->searchable()
                        ->preload()
                        ->columnSpanFull(),
                    Forms\Components\Checkbox::make('marketing_consent')
                        ->label('Klients atļauj izmantot datus mārketingam')
                        ->inline()
                        ->extraAttributes(['class' => 'self-end'])
                        ->extraFieldWrapperAttributes(['class' => 'flex items-end h-full pb-1']),
                ]),
                Grid::make(1)->columnSpan(1)->schema([
                    Forms\Components\Textarea::make('notes_md')->label('Piezīmes')->rows(8),
                    Forms\Components\FileUpload::make('attachments')
                        ->label('Pielikumi')
                        ->helperText('Atļautie failu tipi: '.implode(', ', config('attachments.accepted_mimes'))
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
                        ->directory('attachments'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Vārds')->searchable()->sortable()->weight('bold')
                    ->url(fn (Client $record) => static::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('phone')->label('Tālrunis')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('E-pasts')->searchable()->copyable()->sortable(),
                Tables\Columns\TextColumn::make('personas_kods')->label('Personas kods')->searchable()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('viewings_count')
                    ->counts('viewings')
                    ->label('Apskates')
                    ->sortable(),
                Tables\Columns\TextColumn::make('crm_properties_count')
                    ->counts('crmProperties')
                    ->label('Īpašumi')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->label('Atjaunināts')->since()->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                Tables\Filters\Filter::make('gdpr_pending')->label('Bez GDPR piekrišanas')->query(
                    fn ($query) => $query->whereNull('gdpr_consent_at')->whereNull('gdpr_erased_at')
                ),
                Tables\Filters\SelectFilter::make('owner_user_id')->label('Aģents')
                    ->relationship('owner', 'name'),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()->label('Skatīt')->color('gray'),
                    Actions\Action::make('export_personal_data')
                        ->label('Eksportēt personas datus')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->visible(fn () => auth()->user()?->can('manage'))
                        ->action(function (Client $record) {
                            $url = URL::signedRoute(
                                'gdpr.export',
                                ['email' => $record->email]
                            );
                            Notification::make()
                                ->title('Eksporta saite izveidota')
                                ->body($url)
                                ->success()
                                ->send();
                        }),
                    Actions\Action::make('erase_personal_data')
                        ->label('Dzēst personas datus')
                        ->icon('heroicon-o-trash')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->visible(fn (Client $record) => ! $record->gdpr_erased_at)
                        ->action(function (Client $record) {
                            $record->update([
                                'name' => '—',
                                'phone' => null,
                                'email' => null,
                                'source' => null,
                                'notes_md' => null,
                                'gdpr_erased_at' => now(),
                            ]);
                            Notification::make()
                                ->title('Klienta dati dzēsti')
                                ->warning()
                                ->send();
                        }),
                    Actions\DeleteAction::make()
                        ->label('Dzēst klientu pilnībā')
                        ->modalHeading('Dzēst klientu pilnībā?')
                        ->modalDescription('Klients un visi ar to saistītie CRM dati tiks neatgriezeniski dzēsti.')
                        ->color('gray')
                        ->using(fn (Client $record): ?bool => $record->forceDelete()),
                ])->color('gray'),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CrmPropertiesAsSellerRelationManager::class,
            RelationManagers\CrmPropertiesAsBuyerRelationManager::class,
            RelationManagers\ViewingsRelationManager::class,
            RelationManagers\TasksRelationManager::class,
            RelationManagers\WpformEntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'view' => Pages\ViewClient::route('/{record}'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
