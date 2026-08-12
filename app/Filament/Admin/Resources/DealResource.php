<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\DealResource\Pages;
use App\Models\Client;
use App\Models\Deal;
use App\Models\PropertyCache;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class DealResource extends Resource
{
    protected static ?string $model = Deal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-currency-euro';

    protected static ?string $navigationLabel = 'Darījumi';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?string $modelLabel = 'Darījums';

    protected static ?string $pluralModelLabel = 'Darījumi';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('client_id')->label('Klients')
                ->searchable()
                ->required()
                ->options(fn () => Client::query()->orderBy('name')->limit(20)->pluck('name', 'id')->all())
                ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name),
            Forms\Components\Select::make('property_id')->label('Īpašums')
                ->options(function (mixed $state, Forms\Components\Select $component): array {
                    $query = PropertyCache::query()
                        ->orderBy('title');
                    // Keep the currently-linked property selectable.
                    if ($component->getRecord()?->property_id) {
                        $query->where(fn ($q) => $q->where('status', 'publish')->orWhere('id', $component->getRecord()->property_id));
                    } else {
                        $query->where('status', 'publish');
                    }

                    return $query->pluck('title', 'id')->all();
                })
                ->searchable(),
            Forms\Components\Select::make('stage')->label('Posms')
                ->options(Deal::STAGES)->default('lead')->required(),
            Forms\Components\TextInput::make('value_cents')->label('Vērtība (centos)')->numeric(),
            Forms\Components\DatePicker::make('expected_close_date')->label('Plānotais datums'),
            Forms\Components\Select::make('owner_user_id')->label('Atbildīgais')
                ->relationship('owner', 'name')->searchable()->preload()->optionsLimit(20),
            Forms\Components\FileUpload::make('attachments')
                ->label('Pielikumi')
                ->helperText('Atļauti failu tipi: '.implode(', ', config('attachments.accepted_mimes'))
                    .' · maksimālais izmērs: '.(int) (config('attachments.max_size_kb') / 1024).' MB')
                ->multiple()
                ->reorderable()
                ->deletable()
                ->previewable()
                ->storeFileNamesIn('attachment_original_names')
                ->acceptedFileTypes(config('attachments.accepted_file_types'))
                ->maxSize((int) config('attachments.max_size_kb'))
                ->disk('public')
                ->directory('attachments')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#'),
                Tables\Columns\TextColumn::make('client.name')->label('Klients')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('property.title')->label('Īpašums')->limit(40)->placeholder('—'),
                Tables\Columns\TextColumn::make('stage')
                    ->label('Posms')
                    ->badge()
                    ->colors([
                        'gray' => 'lead',
                        'info' => 'viewing_scheduled',
                        'warning' => 'offer',
                        'primary' => 'reserved',
                        'success' => 'closed_won',
                        'danger' => 'closed_lost',
                    ])
                    ->formatStateUsing(fn ($state) => Deal::STAGES[$state] ?? $state),
                Tables\Columns\TextColumn::make('value_cents')
                    ->label('Vērtība')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 100, 0, '.', ' ').' €' : '—'),
                Tables\Columns\TextColumn::make('expected_close_date')->label('Plānots')->date('d.m.Y'),
                Tables\Columns\TextColumn::make('owner.name')->label('Atbildīgais'),
                Tables\Columns\TextColumn::make('updated_at')->label('Atjaunināts')->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stage')
                    ->label('Posms')
                    ->options(Deal::STAGES),
            ])
            ->actions([
                Actions\EditAction::make()->label('Rediģēt'),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Deal::whereNotIn('stage', ['closed_won', 'closed_lost'])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeals::route('/'),
            'create' => Pages\CreateDeal::route('/create'),
            'edit' => Pages\EditDeal::route('/{record}/edit'),
        ];
    }
}
