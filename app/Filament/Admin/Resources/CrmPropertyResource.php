<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CrmPropertyResource\Pages;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class CrmPropertyResource extends Resource
{
    protected static ?string $model = CrmProperty::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Īpašumi';

    protected static string|UnitEnum|null $navigationGroup = 'Avots';

    protected static ?string $modelLabel = 'Īpašums';

    protected static ?string $pluralModelLabel = 'Īpašumi';

    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->columns(2)->schema([
                Forms\Components\TextInput::make('title')->label('Nosaukums')->required()->maxLength(255),
                Forms\Components\TextInput::make('slug')->label('Slugs')->maxLength(255),
                Forms\Components\Select::make('category')->label('Kategorija')
                    ->options(CrmProperty::CATEGORIES)->searchable(),
                Forms\Components\Select::make('status')->label('Statuss')
                    ->options(CrmProperty::STATUSES)->default('draft')->required(),
                Forms\Components\TextInput::make('price_eur')->label('Cena (€)')->numeric()->prefix('€'),
                Forms\Components\Select::make('owner_user_id')->label('Atbildīgais')
                    ->relationship('owner', 'name')->searchable()->preload(),
            ]),

            Section::make('Īpašuma dati')->columns(2)->schema([
                Forms\Components\TextInput::make('beds')->label('Istabas')->numeric(),
                Forms\Components\TextInput::make('baths')->label('Vannas istabas')->numeric(),
                Forms\Components\TextInput::make('size_m2')->label('Platība (m²)')->numeric(),
                Forms\Components\TextInput::make('land_m2')->label('Zemes platība (m²)')->numeric(),
                Forms\Components\TextInput::make('kadastra_nr')->label('Kadastra nr.')->maxLength(32),
            ]),

            Section::make('Atrašanās vieta')->schema([
                Forms\Components\TextInput::make('city')->label('Pilsēta')->maxLength(128)->nullable()->columnSpan(1),
                Forms\Components\TextInput::make('address')->label('Adrese')->maxLength(255)->nullable()->columnSpan(1),
                Forms\Components\Hidden::make('lat'),
                Forms\Components\Hidden::make('lng'),
                View::make('filament.forms.components.google-maps-picker')
                    ->columnSpanFull()
                    ->viewData([
                        'latField' => 'lat',
                        'lngField' => 'lng',
                    ]),
            ]),

            Section::make()->columnSpanFull()->schema([
                Forms\Components\RichEditor::make('description')
                    ->label('Apraksts')
                    ->extraInputAttributes(['style' => 'min-height: 280px'])
                    ->columnSpanFull(),
            ]),

            Section::make('Pielikumi')->columnSpanFull()->schema([
                Forms\Components\FileUpload::make('attachments')
                    ->label('Fotogrāfijas un plānojumi')
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
                    ->directory('attachments')
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('Nosaukums')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('category')->label('Kategorija')->badge(),
                Tables\Columns\TextColumn::make('status')->label('Statuss')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'published',
                        'warning' => 'expired',
                        'danger' => 'hidden',
                        'info' => 'sold',
                    ])
                    ->formatStateUsing(fn ($state) => CrmProperty::STATUSES[$state] ?? $state),
                Tables\Columns\TextColumn::make('city')->label('Pilsēta'),
                Tables\Columns\TextColumn::make('kadastra_nr')->label('Kadastra nr.')->placeholder('—'),
                Tables\Columns\TextColumn::make('price_eur')->label('Cena')->money('EUR'),
                Tables\Columns\TextColumn::make('owner.name')->label('Atbildīgais'),
                Tables\Columns\TextColumn::make('updated_at')->label('Atjaunināts')->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(CrmProperty::STATUSES),
                Tables\Filters\SelectFilter::make('category')->options(CrmProperty::CATEGORIES),
                Tables\Filters\SelectFilter::make('city')
                    ->options(fn () => CrmProperty::distinct()->pluck('city', 'city')->filter()->toArray()),
            ])
            ->actions([
                Actions\EditAction::make()->label('Rediģēt'),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCrmProperties::route('/'),
            'create' => Pages\CreateCrmProperty::route('/create'),
            'edit' => Pages\EditCrmProperty::route('/{record}/edit'),
        ];
    }
}
