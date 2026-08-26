<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
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
                        'admin' => 'Admin',
                    ])
                    ->searchable()
                    ->default(null)
                    ->nullable()
                    ->native(false)
                    ->dehydrateStateUsing(fn ($state) => $state === 'agent' ? 'aģents' : $state),
                Forms\Components\TextInput::make('password')
                    ->label('Parole (atstāj tukšu, ja nemainīt)')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->maxLength(255),
            ])->columns(2),

            Section::make('Aģenta publiskais profils')->schema([
                Forms\Components\FileUpload::make('avatar_path')
                    ->label('Foto')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios(['1:1'])
                    ->imageEditorViewportWidth(1000)
                    ->imageEditorViewportHeight(1000)
                    ->imageResizeMode('cover')
                    ->imageCropAspectRatio('1:1')
                    ->imageResizeTargetWidth('1000')
                    ->imageResizeTargetHeight('1000')
                    ->disk('public')
                    ->directory('avatars')
                    ->maxSize(5120)
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
                    ->helperText('Kvadrātveida foto — automātiski apgriezts 1:1, saglabāts 1000×1000px, max 5MB')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('phone')->label('Tālrunis')->tel()->maxLength(32)->placeholder('+371 ...'),
                Forms\Components\TextInput::make('position')->label('Amats')->maxLength(255)->placeholder('Aģents'),
                Forms\Components\Textarea::make('description')->label('Apraksts')->rows(4)->maxLength(2000)->helperText('Rādās aģenta lapā un īpašuma kontaktblokā')->columnSpanFull(),
                Forms\Components\TextInput::make('facebook_url')->label('Facebook URL')->url()->maxLength(500)->columnSpan(1),
                Forms\Components\TextInput::make('instagram_url')->label('Instagram URL')->url()->maxLength(500)->columnSpan(1),
                Forms\Components\TextInput::make('linkedin_url')->label('LinkedIn URL')->url()->maxLength(500)->columnSpan(1),
                Forms\Components\TextInput::make('website_url')->label('Mājaslapa')->url()->maxLength(500)->columnSpan(1),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_path')->label('Foto')->disk('public')->circular()->defaultImageUrl(asset('images/default-avatar.png')),
                Tables\Columns\TextColumn::make('name')->label('Vārds')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('E-pasts')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('phone')->label('Tālrunis')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('role')->label('Loma')->badge()->sortable()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'aģents', 'agent' => 'Aģents',
                        'admin' => 'Admin',
                        default => $state ?: '—',
                    })
                    ->color(fn ($state) => match ($state) {
                        'aģents', 'agent' => 'gray',
                        'admin' => 'success',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Loma')
                    ->options([
                        'aģents' => 'Aģents',
                        'admin' => 'Admin',
                    ]),
            ])
            ->actions([
                Actions\EditAction::make()->label('Rediģēt'),
                Actions\DeleteAction::make()->label('Dzēst')->color('gray'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->color('gray'),
                ]),
            ])
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
