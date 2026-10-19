<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KnowledgeCategoryResource\Pages;
use App\Models\KnowledgeCategory;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class KnowledgeCategoryResource extends Resource
{
    protected static ?string $model = KnowledgeCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Kategorijas';

    protected static string|UnitEnum|null $navigationGroup = 'Izglītība';

    protected static ?string $modelLabel = 'Kategorija';

    protected static ?string $pluralModelLabel = 'Kategorijas';

    protected static ?int $navigationSort = 20;

    public static function canAccess(): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nosaukums')->required()->maxLength(255)->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->extraAttributes(['class' => 'pdc-knowledge-categories-table'])->columns([
            Tables\Columns\TextColumn::make('name')->label('Kategorija')->searchable()->sortable()->weight('bold'),
            Tables\Columns\TextColumn::make('articles_count')->label('Materiāli')->counts('articles'),
        ])->actions([
            Actions\EditAction::make()->label('Rediģēt')->color('gray'),
            Actions\DeleteAction::make()
                ->label('Dzēst')
                ->color('danger')
                ->modalHeading('Dzēst kategoriju?')
                ->modalDescription('Kategorija tiks dzēsta. Tās materiāli paliks zināšanu bāzē bez kategorijas.'),
        ])->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKnowledgeCategories::route('/'),
            'create' => Pages\CreateKnowledgeCategory::route('/create'),
            'edit' => Pages\EditKnowledgeCategory::route('/{record}/edit'),
        ];
    }
}
