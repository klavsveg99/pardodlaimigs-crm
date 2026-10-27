<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeArticleResource\Pages;

use App\Filament\Admin\Resources\KnowledgeArticleResource;
use App\Models\KnowledgeAssignment;
use App\Models\KnowledgeCompletion;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewKnowledgeArticle extends ViewRecord
{
    protected static string $resource = KnowledgeArticleResource::class;

    public function getTitle(): string
    {
        return 'Skatīt materiālu';
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        if (auth()->user()?->can('manage') ?? false) {
            $actions[] = Actions\Action::make('assign')
                ->label('Piešķirt lietotājiem')
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->form([
                    Select::make('user_ids')
                        ->label('Lietotāji')
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->options(fn (): array => User::query()->whereIn('role', ['aģents', 'student'])->orderBy('name')->pluck('name', 'id')->all())
                        ->required(),
                    DatePicker::make('due_at')->label('Izpildes termiņš')->native(false)->minDate(today()),
                ])
                ->action(function (array $data): void {
                    abort_unless(auth()->user()?->can('manage'), 403);

                    $userIds = User::query()
                        ->whereIn('role', ['aģents', 'student'])
                        ->whereIn('id', $data['user_ids'])
                        ->pluck('id')
                        ->all();

                    abort_if(count($userIds) !== count($data['user_ids']), 403);

                    foreach ($userIds as $userId) {
                        $assignment = KnowledgeAssignment::query()
                            ->where('knowledge_article_id', $this->record->id)
                            ->where('assigned_user_id', $userId)
                            ->whereNull('completed_at')
                            ->first();

                        if ($assignment) {
                            $assignment->update(['due_at' => $data['due_at'] ?? null]);
                        } else {
                            KnowledgeAssignment::create([
                                'knowledge_article_id' => $this->record->id,
                                'assigned_user_id' => $userId,
                                'assigned_by_user_id' => auth()->id(),
                                'due_at' => $data['due_at'] ?? null,
                            ]);
                        }
                    }

                    Notification::make()->title('Materiāls piešķirts lietotājiem')->success()->send();
                });

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
