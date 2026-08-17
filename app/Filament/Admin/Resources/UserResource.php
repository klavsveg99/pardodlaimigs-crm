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
            Section::make()->schema([
                Forms\Components\TextInput::make('name')->label('Vārds')->required()->maxLength(255),
                Forms\Components\TextInput::make('email')->label('E-pasts')->email()->required()->maxLength(255)->unique(ignoreRecord: true),
                Forms\Components\Select::make('role')->label('Loma')
                    ->options([
                        'aģents' => 'Aģents',
                        'admin' => 'Admin',
                    ])->searchable()->default(null)->nullable(),
                Forms\Components\TextInput::make('password')
                    ->label('Parole (atstāj tukšu, ja nemainīt)')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->maxLength(255),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Vārds')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('email')->label('E-pasts')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('role')->label('Loma')->badge()->sortable(),
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
                Actions\DeleteAction::make()->label('Dzēst'),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
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
