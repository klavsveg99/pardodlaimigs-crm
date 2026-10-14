<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KnowledgeArticleResource\Pages;
use App\Filament\Forms\Components\AttachmentsGrid;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeCategory;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

class KnowledgeArticleResource extends Resource
{
    protected static ?string $model = KnowledgeArticle::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationLabel = 'Zināšanu bāze';

    protected static string|UnitEnum|null $navigationGroup = 'Izglītība';

    protected static ?string $modelLabel = 'Materiāls';

    protected static ?string $pluralModelLabel = 'Zināšanu bāze';

    protected static ?string $slug = 'knowledge';

    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function canDeleteAny(): bool
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->when(! auth()->user()?->can('manage'), fn (Builder $query) => $query->where('is_published', true))
            ->with('category');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Materiāls')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('title')->label('Nosaukums')->required()->maxLength(255)->columnSpanFull(),
                    Forms\Components\Select::make('type')->label('Veids')->options(KnowledgeArticle::TYPES)->required()->default('material'),
                    Forms\Components\Select::make('knowledge_category_id')
                        ->label('Kategorija')
                        ->options(fn (): array => KnowledgeCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                        ->searchable()->preload()->nullable(),
                    Forms\Components\Textarea::make('summary')->label('Īss apraksts')->rows(2)->columnSpanFull(),
                    Forms\Components\RichEditor::make('body')->label('Saturs')->columnSpanFull(),
                    Forms\Components\TextInput::make('video_url')->label('Video saite')->url()->maxLength(2048)->columnSpanFull(),
                    Forms\Components\Repeater::make('steps')
                        ->label('Soļi')
                        ->schema([
                            Forms\Components\TextInput::make('title')->label('Soļa nosaukums')->required()->maxLength(255),
                            Forms\Components\TextInput::make('video_url')->label('Video saite')->url()->maxLength(2048),
                            Forms\Components\RichEditor::make('body')->label('Saturs')->columnSpanFull(),
                        ])
                        ->default([])
                        ->addActionLabel('Pievienot soli')
                        ->reorderableWithButtons()
                        ->collapsible()
                        ->columnSpanFull(),
                    Forms\Components\Toggle::make('is_published')->label('Publicēts')->default(false),
                    AttachmentsGrid::make('attachments')
                        ->label('Faili un video')
                        ->helperText('Atļautie failu tipi: '.implode(', ', config('attachments.accepted_mimes'))
                            .' · maksimālais izmērs: '.(int) (config('attachments.max_size_kb') / 1024).' MB')
                        ->reorderable(false)
                        ->multiselect(false)
                        ->collection('knowledge')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        $record = $schema->getRecord();

        return $schema->components([
            TextEntry::make('title')->hiddenLabel()->size('lg')->weight('bold')->columnSpanFull(),
            TextEntry::make('summary')->hiddenLabel()->placeholder('')->columnSpanFull(),
            Grid::make(2)->schema([
                TextEntry::make('type')->hiddenLabel()->badge()->color('gray')
                    ->formatStateUsing(fn (string $state): string => KnowledgeArticle::TYPES[$state] ?? $state),
                TextEntry::make('category.name')->hiddenLabel()->badge()->color('gray')
                    ->placeholder('')
                    ->visible(fn (?string $state): bool => filled($state)),
            ])->columnSpanFull(),
            View::make('filament.infolists.knowledge-video')
                ->viewData([
                    'url' => $record?->video_url,
                    'embedUrl' => $record && filled($record->video_url) ? $record->getVideoEmbedUrl($record->video_url) : null,
                ])
                ->visible(fn (): bool => filled($record?->video_url))
                ->columnSpanFull(),
            TextEntry::make('body')->label('Saturs')->html()->prose()
                ->visible(fn (?string $state): bool => filled(strip_tags((string) $state)))
                ->columnSpanFull(),
            RepeatableEntry::make('steps')
                ->label('Soļi')
                ->schema([
                    TextEntry::make('title')->hiddenLabel()->weight('bold'),
                    TextEntry::make('video_url')
                        ->hiddenLabel()
                        ->url(fn (?string $state): ?string => $state)
                        ->openUrlInNewTab()
                        ->formatStateUsing(fn (): string => 'Atvērt video')
                        ->visible(fn (?string $state): bool => filled($state)),
                    TextEntry::make('body')->hiddenLabel()->html()->prose()->columnSpanFull(),
                ])
                ->visible(fn ($state): bool => is_array($state) && count($state) > 0)
                ->columnSpanFull(),
            View::make('filament.infolists.knowledge-attachments')
                ->viewData(fn (): array => [
                    'attachments' => $record?->attachments()->where('collection', 'knowledge')->get() ?? collect(),
                ])
                ->visible(fn (): bool => $record?->attachments()->where('collection', 'knowledge')->exists() ?? false)
                ->columnSpanFull(),
            View::make('filament.infolists.knowledge-assignments')
                ->viewData(fn (): array => [
                    'assignments' => $record?->assignments()->with(['assignedTo', 'assignedBy'])->latest()->get() ?? collect(),
                    'isAdmin' => auth()->user()?->can('manage') ?? false,
                ])
                ->visible(fn (): bool => (auth()->user()?->can('manage') ?? false)
                    || ($record?->assignments()->where('assigned_user_id', auth()->id())->exists() ?? false))
                ->columnSpanFull(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Nosaukums')->searchable()->sortable()->weight('bold')->wrap()
                    ->url(fn (KnowledgeArticle $record): string => static::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('type')->label('Veids')->badge()->formatStateUsing(fn (string $state): string => KnowledgeArticle::TYPES[$state] ?? $state)->color('gray'),
                Tables\Columns\TextColumn::make('category.name')->label('Kategorija')->placeholder('-')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('summary')->label('Apraksts')->searchable()->limit(90)->wrap()->toggleable(),
                Tables\Columns\TextColumn::make('body')->label('Saturs')->searchable()->formatStateUsing(fn (?string $state): string => strip_tags((string) $state))->limit(100)->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_published')->label('Publicēts')->boolean()->visible(fn (): bool => auth()->user()?->can('manage') ?? false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('knowledge_category_id')->label('Kategorija')
                    ->options(fn (): array => KnowledgeCategory::query()->orderBy('name')->pluck('name', 'id')->all()),
                Tables\Filters\SelectFilter::make('type')->label('Veids')->options(KnowledgeArticle::TYPES),
                Tables\Filters\TernaryFilter::make('is_published')->label('Publicēšana')
                    ->visible(fn (): bool => auth()->user()?->can('manage') ?? false),
            ])
            ->actions([
                Actions\ViewAction::make()->label('Atvērt')->color('gray'),
                Actions\EditAction::make()->label('Rediģēt')->visible(fn (): bool => auth()->user()?->can('manage') ?? false),
                Actions\DeleteAction::make()
                    ->label('Dzēst')
                    ->visible(fn (): bool => auth()->user()?->can('manage') ?? false)
                    ->modalHeading('Dzēst materiālu?')
                    ->modalDescription('Materiāls, tā faili un visi piešķīrumi tiks neatgriezeniski dzēsti.'),
            ])
            ->defaultSort('title')
            ->searchPlaceholder('Meklēt materiālos')
            ->emptyStateHeading('Materiālu vēl nav');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListKnowledgeArticles::route('/'),
            'create' => Pages\CreateKnowledgeArticle::route('/create'),
            'view' => Pages\ViewKnowledgeArticle::route('/{record}'),
            'edit' => Pages\EditKnowledgeArticle::route('/{record}/edit'),
        ];
    }
}
