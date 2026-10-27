<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\KnowledgeArticleResource\Pages;
use App\Filament\Forms\Components\AttachmentsGrid;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeAssignment;
use App\Models\KnowledgeCategory;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Alignment;
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

    public static function canRestore(Model $record): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function canRestoreAny(): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function canForceDelete(Model $record): bool
    {
        return auth()->user()?->can('manage') ?? false;
    }

    public static function canForceDeleteAny(): bool
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
        $query = parent::getEloquentQuery()->with('category');
        $user = auth()->user();

        if ($user?->can('manage')) {
            return $query;
        }

        // Fotogrāfs redz tikai viņam piešķirtos materiālus. Aģents un
        // students papildus redz materiālus ar atzīmi "Pieejams visiem"
        // (bez piešķiršanas un izpildes termiņa).
        if ($user?->isPhoto()) {
            return $query
                ->whereHas(
                    'assignments',
                    fn (Builder $assignment): Builder => $assignment->where('assigned_user_id', $user->id),
                )
                ->with([
                    'assignments' => fn ($assignment) => $assignment->where('assigned_user_id', $user->id),
                    'completions' => fn ($completion) => $completion->where('user_id', $user->id),
                ]);
        }

        return $query
            ->where(function (Builder $query) use ($user): void {
                $query->where('is_public', true)
                    ->orWhereHas(
                        'assignments',
                        fn (Builder $assignment): Builder => $assignment->where('assigned_user_id', $user?->id),
                    );
            })
            // Saraksta statusa kolonnai - tikai šī lietotāja ieraksti.
            ->with([
                'assignments' => fn ($assignment) => $assignment->where('assigned_user_id', $user?->id),
                'completions' => fn ($completion) => $completion->where('user_id', $user?->id),
            ]);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->label('Nosaukums')->required()->maxLength(255)->columnSpanFull(),
            Forms\Components\Select::make('type')
                ->label('Veids')
                ->options(KnowledgeArticle::TYPES)
                ->required()
                ->default('material')
                ->live(),
            Forms\Components\Select::make('knowledge_category_id')
                ->label('Kategorija')
                ->options(fn (): array => KnowledgeCategory::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()->preload()->nullable(),
            Forms\Components\Toggle::make('is_public')
                ->label('Pieejams visiem')
                ->helperText('Redzams visiem aģentiem un studentiem arī bez piešķiršanas.')
                ->default(false),
            Forms\Components\Textarea::make('summary')->label('Īss apraksts')->rows(2)->columnSpanFull(),
            Forms\Components\RichEditor::make('body')->label('Saturs')->columnSpanFull(),
            Forms\Components\Repeater::make('video_urls')
                ->label('Video saites')
                ->simple(TextInput::make('video_url')->label('Video saite')->url()->required()->maxLength(2048))
                ->default([])
                ->addActionLabel('Pievienot video')
                ->addActionAlignment(Alignment::Start)
                ->reorderableWithButtons()
                ->columnSpanFull(),
            Forms\Components\Repeater::make('steps')
                ->label('Soļi')
                ->schema([
                    TextInput::make('title')->label('Soļa nosaukums')->required()->maxLength(255),
                    TextInput::make('video_url')->label('Video saite')->url()->maxLength(2048),
                    Forms\Components\RichEditor::make('body')->label('Saturs')->columnSpanFull(),
                ])
                ->default([])
                ->addActionLabel('Pievienot soli')
                ->addActionAlignment(Alignment::Start)
                ->reorderableWithButtons()
                ->collapsible()
                ->visible(fn (Get $get): bool => $get('type') === 'course')
                ->columnSpanFull(),
            AttachmentsGrid::make('attachments')
                ->label('Pielikumi')
                ->helperText('Atļautie failu tipi: '.implode(', ', config('attachments.accepted_mimes'))
                    .' · maksimālais izmērs: '.(int) (config('attachments.max_size_kb') / 1024).' MB')
                ->reorderable(false)
                ->multiselect(false)
                ->listMode()
                ->collection('knowledge')
                ->columnSpanFull(),
        ])->columns(2);
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
                    'videoUrls' => $record?->video_urls ?? [],
                    'article' => $record,
                ])
                ->visible(fn (): bool => filled($record?->video_urls))
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
                ->visible(fn ($state): bool => $record?->type === 'course' && is_array($state) && count($state) > 0)
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
                    'completions' => (auth()->user()?->can('manage') ?? false)
                        ? ($record?->completions()->with('user')->latest('completed_at')->get() ?? collect())
                        : collect(),
                    'myCompletion' => $record?->completions()->where('user_id', auth()->id())->first(),
                    'isAdmin' => auth()->user()?->can('manage') ?? false,
                    'isPublic' => (bool) $record?->is_public,
                ])
                ->visible(fn (): bool => (auth()->user()?->can('manage') ?? false)
                    || ($record?->is_public ?? false)
                    || ($record?->assignments()->where('assigned_user_id', auth()->id())->exists() ?? false))
                ->columnSpanFull(),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'pdc-knowledge-table'])
            ->columns([
                Tables\Columns\TextColumn::make('title')->label('Nosaukums')->searchable()->sortable()->weight('bold')->wrap()
                    ->url(fn (KnowledgeArticle $record): ?string => $record->trashed()
                        ? null
                        : static::getUrl('view', ['record' => $record])),
                Tables\Columns\TextColumn::make('type')->label('Veids')->badge()->formatStateUsing(fn (string $state): string => KnowledgeArticle::TYPES[$state] ?? $state)->color('gray'),
                Tables\Columns\IconColumn::make('is_public')->label('Visiem')->boolean()
                    ->visible(fn (): bool => auth()->user()?->can('manage') ?? false),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statuss')
                    ->badge()
                    ->visible(fn (): bool => ! (auth()->user()?->can('manage') ?? false))
                    ->getStateUsing(function (KnowledgeArticle $record): string {
                        $hasPending = $record->assignments->contains(
                            fn (KnowledgeAssignment $assignment): bool => $assignment->completed_at === null,
                        );

                        if ($hasPending) {
                            return 'Jāizpilda';
                        }

                        return $record->completions->isNotEmpty() || $record->assignments->isNotEmpty()
                            ? 'Pabeigts'
                            : 'Pieejams';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Pabeigts' => 'success',
                        'Jāizpilda' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('category.name')->label('Kategorija')->placeholder('-')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('summary')->label('Apraksts')->searchable()->limit(90)->wrap()->toggleable(),
                Tables\Columns\TextColumn::make('body')->label('Saturs')->searchable()->formatStateUsing(fn (?string $state): string => strip_tags((string) $state))->limit(100)->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('knowledge_category_id')->label('Kategorija')
                    ->options(fn (): array => KnowledgeCategory::query()->orderBy('name')->pluck('name', 'id')->all()),
                Tables\Filters\SelectFilter::make('type')->label('Veids')->options(KnowledgeArticle::TYPES),
                Tables\Filters\TrashedFilter::make()
                    ->label('Dzēstie')
                    ->placeholder('Aktīvie materiāli')
                    ->trueLabel('Visi materiāli')
                    ->falseLabel('Tikai dzēstie')
                    ->visible(fn (): bool => auth()->user()?->can('manage') ?? false),
            ])
            ->actions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()->label('Atvērt')->color('gray')
                        ->visible(fn (KnowledgeArticle $record): bool => ! $record->trashed()),
                    Actions\EditAction::make()->label('Rediģēt')->color('gray')
                        ->visible(fn (KnowledgeArticle $record): bool => ! $record->trashed()
                            && (auth()->user()?->can('manage') ?? false)),
                    Actions\DeleteAction::make()
                        ->label('Dzēst')
                        ->color('danger')
                        ->visible(fn (KnowledgeArticle $record): bool => ! $record->trashed()
                            && (auth()->user()?->can('manage') ?? false))
                        ->modalHeading('Pārvietot materiālu uz dzēstajiem?')
                        ->modalDescription('Materiālu varēs atjaunot no dzēsto materiālu saraksta.')
                        ->modalSubmitActionLabel('Dzēst'),
                    Actions\RestoreAction::make()->label('Atjaunot')->color('gray')
                        ->visible(fn (KnowledgeArticle $record): bool => $record->trashed()
                            && (auth()->user()?->can('manage') ?? false)),
                    Actions\ForceDeleteAction::make()
                        ->label('Izdzēst neatgriezeniski')
                        ->color('danger')
                        ->visible(fn (KnowledgeArticle $record): bool => $record->trashed()
                            && (auth()->user()?->can('manage') ?? false))
                        ->modalHeading('Izdzēst materiālu neatgriezeniski?')
                        ->modalDescription('Materiāls, tā faili un piešķīrumi tiks neatgriezeniski dzēsti.')
                        ->modalSubmitActionLabel('Izdzēst neatgriezeniski'),
                ])->color('gray'),
            ])
            ->recordUrl(fn (KnowledgeArticle $record): ?string => $record->trashed()
                ? null
                : static::getUrl('view', ['record' => $record]))
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
