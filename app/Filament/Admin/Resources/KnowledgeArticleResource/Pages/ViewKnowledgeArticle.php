<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeArticleResource\Pages;

use App\Filament\Admin\Resources\KnowledgeArticleResource;
use App\Filament\Admin\Resources\KnowledgeArticleResource\Pages\Concerns\AssignsKnowledgeArticle;
use App\Models\KnowledgeAssignment;
use App\Models\KnowledgeCompletion;
use App\Models\KnowledgeCourseAssignment;
use App\Models\User;
use App\Services\KnowledgeProgress;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ViewKnowledgeArticle extends ViewRecord
{
    use AssignsKnowledgeArticle;

    protected static string $resource = KnowledgeArticleResource::class;

    public function getTitle(): string
    {
        return 'Skatīt materiālu';
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            $this->getInfolistContentComponent(),
            $this->getRelationManagersContentComponent(),
            View::make('filament.knowledge.article-comments')
                ->viewData(['article' => $this->getRecord()]),
            View::make('filament.knowledge.article-audience')
                ->viewData(fn (): array => ['rows' => $this->audienceRows()])
                ->visible(fn (): bool => auth()->user()?->can('manage') ?? false),
        ]);
    }

    /**
     * Visi lietotāji, kuriem materiāls ir pieejams (obligātais saturs, tiešs
     * piešķīrums vai kurss), ar izpildes statusu.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function audienceRows(): array
    {
        $categoryIds = $this->record->categories()->pluck('knowledge_categories.id');

        $courseUserIds = $categoryIds->isEmpty()
            ? collect()
            : KnowledgeCourseAssignment::query()
                ->whereIn('knowledge_category_id', $categoryIds)
                ->pluck('assigned_user_id');

        $assignmentsByUser = $this->record->assignments()
            ->orderBy('created_at')
            ->get()
            ->groupBy('assigned_user_id');

        $completionsByUser = $this->record->completions()->get()->keyBy('user_id');

        return User::query()
            ->knowledgeAudience()
            ->orderBy('name')
            ->get()
            ->filter(fn (User $user): bool => $this->record->is_public
                || $courseUserIds->contains($user->id)
                || $assignmentsByUser->has($user->id))
            ->map(function (User $user) use ($courseUserIds, $assignmentsByUser, $completionsByUser): array {
                $assignments = $assignmentsByUser->get($user->id, collect());
                $pendingAssignment = $assignments->firstWhere('completed_at', null);
                $completedAssignment = $assignments->firstWhere('completed_at', '!==', null);
                $completion = $completionsByUser->get($user->id);

                $completedAt = $completedAssignment?->completed_at ?? $completion?->completed_at;

                return [
                    'name' => $user->name,
                    'role' => $user->role_label,
                    'sources' => array_values(array_filter([
                        $this->record->is_public ? 'Obligāts saturs' : null,
                        $assignments->isNotEmpty() ? 'Piešķirts' : null,
                        $courseUserIds->contains($user->id) ? 'Kurss' : null,
                    ])),
                    'due_at' => $pendingAssignment?->due_at ?? $completedAssignment?->due_at,
                    'completed_at' => $completedAt,
                    'rating' => $completedAssignment?->rating ?? $completion?->rating,
                    'completed' => $completedAt !== null,
                ];
            })
            ->sortBy(fn (array $row): string => ($row['completed'] ? '1' : '0').mb_strtolower($row['name']))
            ->values()
            ->all();
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (auth()->user()?->can('manage') ?? false) {
            $actions[] = $this->assignKnowledgeAction();

            $actions[] = Actions\EditAction::make()->label('Rediģēt materiālu')->color('gray');
            $actions[] = Actions\DeleteAction::make()
                ->label('Dzēst materiālu')
                ->color('danger')
                ->modalHeading('Pārvietot materiālu uz dzēstajiem?')
                ->modalDescription('Materiālu varēs atjaunot no dzēsto materiālu saraksta.')
                ->modalSubmitActionLabel('Dzēst')
                ->successRedirectUrl(KnowledgeArticleResource::getUrl('index'));
        }

        $actions[] = Actions\Action::make('complete_assignment')
            ->label('Atzīmēt kā pabeigtu')
            ->icon('heroicon-o-check')
            ->color('success')
            ->visible(fn (): bool => (! (auth()->user()?->can('manage') ?? false) || $this->record->is_public) && ! $this->hasCompleted())
            ->form([
                Select::make('rating')
                    ->label('Vērtējums')
                    ->options([
                        1 => '1 - Ļoti vāji',
                        2 => '2 - Vāji',
                        3 => '3 - Apmierinoši',
                        4 => '4 - Labi',
                        5 => '5 - Teicami',
                    ])
                    ->required(),
            ])
            ->modalHeading('Atzīmēt materiālu kā pabeigtu')
            ->modalDescription('Novērtē materiālu, lai palīdzētu uzlabot zināšanu bāzi.')
            ->modalSubmitActionLabel('Pabeigt')
            ->action(function (array $data): void {
                $user = auth()->user();
                $assignment = $this->pendingAssignment();

                if ($assignment) {
                    $assignment->update([
                        'completed_at' => now(),
                        'rating' => (int) $data['rating'],
                    ]);
                } else {
                    $courseAssigned = $this->record->categories()
                        ->whereHas(
                            'courseAssignments',
                            fn (Builder $query): Builder => $query->where('assigned_user_id', $user->id),
                        )
                        ->exists();

                    if (! $this->record->is_public && ! $courseAssigned) {
                        return;
                    }

                    KnowledgeCompletion::query()->updateOrCreate(
                        [
                            'knowledge_article_id' => $this->record->id,
                            'user_id' => $user->id,
                        ],
                        [
                            'completed_at' => now(),
                            'rating' => (int) $data['rating'],
                        ],
                    );
                }

                app(KnowledgeProgress::class)->syncForArticle($user, $this->record);

                Notification::make()->title('Materiāls atzīmēts kā pabeigts')->success()->send();
            });

        return $actions;
    }

    public function pendingAssignment(): ?KnowledgeAssignment
    {
        return $this->record->assignments()
            ->where('assigned_user_id', auth()->id())
            ->whereNull('completed_at')
            ->oldest()
            ->first();
    }

    /**
     * Vai lietotājs jau ir pabeidzis materiālu - vai nu izpildot piešķīrumu,
     * vai pats atzīmējot brīvi pieejamu materiālu.
     */
    public function hasCompleted(): bool
    {
        if ($this->pendingAssignment() !== null) {
            return false;
        }

        return $this->record->completions()
            ->where('user_id', auth()->id())
            ->whereNotNull('completed_at')
            ->exists()
            || $this->record->assignments()
                ->where('assigned_user_id', auth()->id())
                ->whereNotNull('completed_at')
                ->exists();
    }
}
