<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\KnowledgeArticleResource\Pages\Concerns;

use App\Models\KnowledgeAssignment;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

/**
 * "Piešķirt lietotājiem" darbība, kas vienādi pieejama materiāla skata un
 * rediģēšanas lapās.
 */
trait AssignsKnowledgeArticle
{
    protected function assignKnowledgeAction(): Actions\Action
    {
        return Actions\Action::make('assign')
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
    }
}
