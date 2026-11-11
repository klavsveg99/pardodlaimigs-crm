<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Filament\Admin\Resources\KnowledgeArticleResource;
use App\Models\KnowledgeComment;
use App\Models\KnowledgeCourseAssignment;
use Livewire\Component;

/**
 * Komentāri vienam mērķim: materiālam, konkrētam solim vai kursam.
 * Komentārus un atbildes redz visi, kam ir pieeja attiecīgajam mērķim.
 */
class KnowledgeComments extends Component
{
    public int $articleId = 0;

    public int $categoryId = 0;

    public ?int $stepIndex = null;

    public string $body = '';

    public ?int $replyTo = null;

    public string $replyBody = '';

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
        ])->notifyAdmins();

        $this->body = '';
        $this->dispatch('knowledge-comment-added');
    }

    public function startReply(int $commentId): void
    {
        $this->authorizeTarget();

        $this->replyTo = $commentId;
        $this->replyBody = '';
        $this->resetValidation('replyBody');
    }

    public function cancelReply(): void
    {
        $this->replyTo = null;
        $this->replyBody = '';
        $this->resetValidation('replyBody');
    }

    public function addReply(): void
    {
        $this->authorizeTarget();

        $this->validate([
            'replyBody' => ['required', 'string', 'max:2000'],
        ]);

        $parent = KnowledgeComment::query()->whereKey($this->replyTo)->first();

        abort_unless($parent && $this->sameTarget($parent), 403);

        KnowledgeComment::create([
            'user_id' => auth()->id(),
            'parent_id' => $parent->id,
            'knowledge_article_id' => $parent->knowledge_article_id,
            'knowledge_category_id' => $parent->knowledge_category_id,
            'step_index' => $parent->step_index,
            'body' => trim($this->replyBody),
        ])->notifyAdmins();

        $this->cancelReply();
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

        $grouped = $comments->groupBy(fn (KnowledgeComment $comment): int => $comment->parent_id ?? 0);

        $build = function (int $parentId) use (&$build, $grouped): array {
            return $grouped->get($parentId, collect())
                ->map(fn (KnowledgeComment $comment): array => [
                    'comment' => $comment,
                    'replies' => $build((int) $comment->id),
                ])
                ->all();
        };

        return view('livewire.knowledge-comments', [
            'threads' => $build(0),
        ]);
    }

    private function sameTarget(KnowledgeComment $comment): bool
    {
        if ($this->articleId > 0) {
            return (int) $comment->knowledge_article_id === $this->articleId
                && $comment->knowledge_category_id === null
                && $comment->step_index === $this->stepIndex;
        }

        return (int) $comment->knowledge_category_id === $this->categoryId
            && $comment->knowledge_article_id === null
            && $comment->step_index === null;
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
