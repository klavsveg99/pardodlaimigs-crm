<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Filament\Admin\Resources\KnowledgeArticleResource;
use App\Models\KnowledgeComment;
use App\Models\KnowledgeCourseAssignment;
use Livewire\Component;

/**
 * Komentāri vienam mērķim: materiālam, konkrētam kursa solim vai kategorijai
 * (kursam). Vienu komponenti izmanto gan aģenti, gan administratori.
 */
class KnowledgeComments extends Component
{
    public int $articleId = 0;

    public int $categoryId = 0;

    public ?int $stepIndex = null;

    public string $body = '';

    public function mount(int $articleId = 0, int $categoryId = 0, ?int $stepIndex = null): void
    {
        $this->articleId = $articleId;
        $this->categoryId = $categoryId;
        $this->stepIndex = $stepIndex;
    }

    public function addComment(): void
    {
        $this->authorizeTarget();

        $this->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        KnowledgeComment::create([
            'user_id' => auth()->id(),
            'knowledge_article_id' => $this->articleId ?: null,
            'knowledge_category_id' => $this->categoryId ?: null,
            'step_index' => $this->articleId ? $this->stepIndex : null,
            'body' => trim($this->body),
        ]);

        $this->body = '';
        $this->dispatch('knowledge-comment-added');
    }

    public function render()
    {
        $this->authorizeTarget();

        $comments = KnowledgeComment::query()
            ->with('user')
            ->when($this->articleId > 0, fn ($query) => $query
                ->where('knowledge_article_id', $this->articleId)
                ->where('step_index', $this->stepIndex))
            ->when($this->articleId === 0 && $this->categoryId > 0, fn ($query) => $query
                ->where('knowledge_category_id', $this->categoryId))
            ->oldest()
            ->get();

        return view('livewire.knowledge-comments', [
            'comments' => $comments,
        ]);
    }

    private function authorizeTarget(): void
    {
        $user = auth()->user();

        abort_unless($user, 403);

        if ($this->articleId > 0) {
            abort_unless(
                KnowledgeArticleResource::getEloquentQuery()->whereKey($this->articleId)->exists(),
                403,
            );

            return;
        }

        abort_unless($this->categoryId > 0, 403);

        abort_unless(
            $user->can('manage') || KnowledgeCourseAssignment::query()
                ->where('knowledge_category_id', $this->categoryId)
                ->where('assigned_user_id', $user->id)
                ->exists(),
            403,
        );
    }
}
