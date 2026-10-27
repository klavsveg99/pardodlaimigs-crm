<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeArticleResource\Pages;

use App\Filament\Admin\Resources\KnowledgeArticleResource;
use App\Filament\Admin\Resources\KnowledgeArticleResource\Pages\Concerns\AssignsKnowledgeArticle;
use App\Models\KnowledgeAssignment;
use App\Models\KnowledgeCompletion;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewKnowledgeArticle extends ViewRecord
{
    use AssignsKnowledgeArticle;

    protected static string $resource = KnowledgeArticleResource::class;

    public function getTitle(): string
    {
        return 'Skatīt materiālu';
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
            ->visible(fn (): bool => ! (auth()->user()?->can('manage') ?? false) && ! $this->hasCompleted())
            ->action(function (): void {
                $assignment = $this->pendingAssignment();

                if ($assignment) {
                    $assignment->update(['completed_at' => now()]);
                } elseif ($this->record->is_public) {
                    KnowledgeCompletion::query()->updateOrCreate(
                        [
                            'knowledge_article_id' => $this->record->id,
                            'user_id' => auth()->id(),
                        ],
                        ['completed_at' => now()],
                    );
                } else {
                    return;
                }

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

        return $this->record->completions()->where('user_id', auth()->id())->exists()
            || $this->record->assignments()
                ->where('assigned_user_id', auth()->id())
                ->whereNotNull('completed_at')
                ->exists();
    }
}
