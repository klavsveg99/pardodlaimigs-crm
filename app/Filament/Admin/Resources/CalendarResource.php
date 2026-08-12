<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CalendarResource\Pages;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

class CalendarResource extends Resource
{
    protected static ?string $model = null;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Kalendārs';

    protected static string|UnitEnum|null $navigationGroup = 'Darbplūsma';

    protected static ?string $modelLabel = 'Kalendārs';

    protected static ?string $pluralModelLabel = 'Kalendārs';

    protected static ?int $navigationSort = 29;

    protected static ?string $slug = 'kalendars';

    public static function table(Table $table): Table
    {
        return $table;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\CalendarPage::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
