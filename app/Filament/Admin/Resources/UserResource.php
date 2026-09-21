<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Filament\Forms\Components\AvatarEditor;
use App\Filament\Forms\Components\PhoneInput;
use App\Models\User;
use App\Rules\Phone;
use App\Support\PhoneFormat;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Aģenti';

    protected static string|UnitEnum|null $navigationGroup = 'Sistēma';

    protected static ?string $modelLabel = 'Lietotājs';

    protected static ?string $pluralModelLabel = 'Lietotāji';

    protected static ?string $recordRouteKeyName = 'slug';

    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Pamatdati')->schema([
                Forms\Components\TextInput::make('name')->label('Vārds')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->label('E-pasts')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                Forms\Components\Select::make('role')->label('Loma')
                    ->options([
                        'aģents' => 'Aģents',
                        'photo' => 'Fotogrāfs',
                        'admin' => 'Administrators',
                    ])
                    ->searchable()
                    ->default('aģents')
                    ->required()
                    ->native(false)
                    ->live()
                    ->dehydrateStateUsing(fn ($state) => $state === 'agent' ? 'aģents' : $state),
                Forms\Components\TextInput::make('password')
                    ->label('Parole')
                    ->hint(fn (string $operation): ?string => $operation === 'edit' ? 'Atstāj tukšu, ja nemainīt' : null)
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn ($state) => filled($state))
                    ->maxLength(255)
                    ->confirmed(),
                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Parole atkārtoti')
                    ->password()
                    ->revealable()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false)
                    ->maxLength(255),
            ])->columns(2),

            Section::make('Aģenta publiskais profils')->schema([
                AvatarEditor::make('avatar_path')
                    ->label('Foto')
                    ->helperText('Kvadrātveida foto — 1:1, 1000×1000px, max 5MB. Izmanto redaktoru, lai apgrieztu un apvērstu.')
                    ->columnSpanFull(),
                PhoneInput::make('phone')->label('Tālrunis')->maxLength(20)->rule(new Phone),
                Forms\Components\TextInput::make('position')->label('Amats')->maxLength(255)->placeholder('Aģents'),
                Forms\Components\Textarea::make('description')->label('Apraksts')->rows(4)->maxLength(2000)->helperText('Rādās aģenta lapā un īpašuma kontaktblokā')->columnSpanFull(),
                Forms\Components\TextInput::make('facebook_url')->label('Facebook URL')->url()->maxLength(500)->columnSpan(1),
                Forms\Components\TextInput::make('instagram_url')->label('Instagram URL')->url()->maxLength(500)->columnSpan(1),
                Forms\Components\TextInput::make('linkedin_url')->label('LinkedIn URL')->url()->maxLength(500)->columnSpan(1),
                Forms\Components\TextInput::make('website_url')->label('Mājaslapa')->url()->maxLength(500)->columnSpan(1),
                Forms\Components\TextInput::make('office_address')->label('Biroja adrese')->maxLength(500)->placeholder('Rīga, Brīvības iela 1')->columnSpanFull(),
                Forms\Components\RichEditor::make('email_signature')
                    ->label('E-pasta paraksts')
                    ->helperText('Tiek automātiski pievienots visiem e-pastiem, ko šis lietotājs nosūta no CRM.')
                    ->toolbarButtons(['bold', 'italic', 'underline', 'link', 'bulletList', 'orderedList'])
                    ->visible(fn (Get $get): bool => ($get('role') ?? 'aģents') !== 'admin')
                    ->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        // Bulk delete depends on the active tab: on "Dzēstie" the rows are
        // already trashed, so only a permanent delete makes sense.
        $trashSelected = Actions\DeleteBulkAction::make('trash_selected')
            ->label('Dzēst')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Pārvietot izvēlētos aģentus uz "Dzēstie"?')
            ->modalDescription('Aģenti paliks sadaļā "Dzēstie" un būs atjaunojami.')
            ->modalSubmitActionLabel('Dzēst')
            ->deselectRecordsAfterCompletion();
        $trashSelected->visible(fn (): bool => ($trashSelected->getLivewire()?->activeTab ?? 'active') !== 'deleted');

        $forceDeleteSelected = Actions\ForceDeleteBulkAction::make('force_delete_selected')
            ->label('Izdzēst neatgriezeniski')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Vai tiešām neatgriezeniski dzēst izvēlētos aģentus?')
            ->modalDescription('Ieraksti tiks pilnībā izņemti no CRM, un tos vairs nevarēs atjaunot.')
            ->modalSubmitActionLabel('Izdzēst neatgriezeniski')
            ->deselectRecordsAfterCompletion();
        $forceDeleteSelected->visible(fn (): bool => ($forceDeleteSelected->getLivewire()?->activeTab ?? 'active') === 'deleted'
            && (auth()->user()?->can('manage') ?? false));

        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_path')->label('Foto')->disk('public')->circular()->defaultImageUrl(asset('images/no-photo.svg')),
                Tables\Columns\TextColumn::make('name')->label('Vārds')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('E-pasts')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('Tālrunis')->searchable()->sortable()->toggleable()->formatStateUsing(fn ($state) => PhoneFormat::display((string) $state)),
                Tables\Columns\TextColumn::make('office_address')->label('Birojs')->searchable()->toggleable(isToggledHiddenByDefault: true)->placeholder('—'),
                Tables\Columns\TextColumn::make('role')->label('Loma')->badge()->sortable()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'aģents', 'agent' => 'Aģents',
                        'photo' => 'Fotogrāfs',
                        'admin' => 'Administrators',
                        default => $state ?: '—',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Loma')
                    ->options([
                        'aģents' => 'Aģents',
                        'photo' => 'Fotogrāfs',
                        'admin' => 'Administrators',
                    ]),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->label('Rediģēt')->color('gray')
                        ->visible(fn (User $record): bool => ! $record->trashed()),
                    Actions\DeleteAction::make()->label('Dzēst')->color('danger')
                        ->visible(fn (User $record): bool => ! $record->trashed() && $record->id !== auth()->id())
                        ->modalHeading('Pārvietot aģentu uz "Dzēstie"?')
                        ->modalDescription('Aģents pazudīs no aktīvā saraksta, bet paliks sadaļā "Dzēstie" un būs atjaunojams.')
                        ->modalSubmitActionLabel('Dzēst'),
                    Actions\RestoreAction::make()->label('Atjaunot')->color('gray')
                        ->visible(fn (User $record): bool => $record->trashed())
                        ->modalHeading('Atjaunot aģentu?')
                        ->modalDescription('Aģents atgriezīsies aktīvajā sarakstā.')
                        ->modalSubmitActionLabel('Atjaunot'),
                    Actions\ForceDeleteAction::make()->label('Izdzēst neatgriezeniski')->color('danger')
                        ->visible(fn (User $record): bool => $record->trashed() && $record->id !== auth()->id() && (auth()->user()?->can('manage') ?? false))
                        ->modalHeading('Vai tiešām neatgriezeniski dzēst šo aģentu?')
                        ->modalDescription('Aģents tiks pilnībā izņemts no CRM. To vairs nevarēs atjaunot, un viņa īpašumiem tiks noņemts atbildīgais.')
                        ->modalSubmitActionLabel('Izdzēst neatgriezeniski'),
                ])->color('gray'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    $trashSelected,
                    $forceDeleteSelected,
                ]),
            ])
            ->recordUrl(fn (User $record): ?string => $record->trashed() ? null : static::getUrl('edit', ['record' => $record]))
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
