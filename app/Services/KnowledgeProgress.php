<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\KnowledgeArticle;
use App\Models\KnowledgeAssignment;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeCompletion;
use App\Models\KnowledgeCourseAssignment;
use App\Models\User;

/**
 * Kursa (kategorijas) izpildes aprēķins. Materiāls skaitās pabeigts, ja
 * lietotājam ir pabeigts piešķīrums vai paša atzīmēta izpilde.
 */
class KnowledgeProgress
{
    /**
     * @return array{total: int, completed: int, percent: int}
     */
    public function courseProgress(User $user, KnowledgeCategory $category): array
    {
        $articleIds = $category->articles()->pluck('id');

        $total = $articleIds->count();
        if ($total === 0) {
            return ['total' => 0, 'completed' => 0, 'percent' => 0];
        }

        $completed = KnowledgeAssignment::query()
            ->where('assigned_user_id', $user->id)
            ->whereIn('knowledge_article_id', $articleIds)
            ->whereNotNull('completed_at')
            ->pluck('knowledge_article_id')
            ->merge(
                KnowledgeCompletion::query()
                    ->where('user_id', $user->id)
                    ->whereIn('knowledge_article_id', $articleIds)
                    ->whereNotNull('completed_at')
                    ->pluck('knowledge_article_id')
            )
            ->unique()
            ->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percent' => (int) round($completed / $total * 100),
        ];
    }

    /**
     * Ja visi kursa materiāli ir pabeigti, atzīmē arī kursa piešķīrumu kā
     * pabeigtu. Atgriež atjaunināto piešķīrumu.
     */
    public function syncCourseCompletion(User $user, KnowledgeCategory $category): ?KnowledgeCourseAssignment
    {
        $assignment = $category->courseAssignments()
            ->where('assigned_user_id', $user->id)
            ->first();

        if (! $assignment) {
            return null;
        }

        $progress = $this->courseProgress($user, $category);

        if ($progress['total'] > 0 && $progress['completed'] >= $progress['total'] && $assignment->completed_at === null) {
            $assignment->update(['completed_at' => now()]);
        }

        return $assignment->refresh();
    }

    public function syncForArticle(User $user, KnowledgeArticle $article): void
    {
        if ($article->category) {
            $this->syncCourseCompletion($user, $article->category);
        }
    }
}
