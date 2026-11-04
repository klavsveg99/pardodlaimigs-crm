<?php

declare(strict_types=1);

namespace App\Services;

use App\Filament\Admin\Resources\KnowledgeArticleResource;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeAssignment;
use App\Models\KnowledgeCompletion;
use App\Models\KnowledgeCourseAssignment;
use App\Models\User;

/**
 * Lietotāja zināšanu kopsavilkums: piešķirtie kursi un materiāli, kas
 * redzami visiem (obligāti) vai piešķirti konkrēti šim lietotājam.
 */
class KnowledgeRecap
{
    public function __construct(private readonly KnowledgeProgress $progress) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function courses(User $user): array
    {
        return KnowledgeCourseAssignment::query()
            ->with('category')
            ->where('assigned_user_id', $user->getKey())
            ->get()
            ->map(function (KnowledgeCourseAssignment $assignment) use ($user): array {
                $stats = $assignment->category
                    ? $this->progress->courseProgress($user, $assignment->category)
                    : ['total' => 0, 'completed' => 0, 'percent' => 0];

                return [
                    'id' => $assignment->id,
                    'name' => $assignment->category?->name ?? 'Dzēsts kurss',
                    'stats' => $stats,
                    'due_at' => $assignment->due_at,
                    'completed_at' => $assignment->completed_at,
                    'completed' => $assignment->completed_at !== null,
                    'overdue' => $assignment->completed_at === null
                        && $assignment->due_at !== null
                        && $assignment->due_at->isPast(),
                    'rating' => $assignment->rating,
                    'status' => $assignment->completed_at !== null ? 'Pabeigts' : 'Jāizpilda',
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function materials(User $user): array
    {
        $assignmentsByArticle = KnowledgeAssignment::query()
            ->where('assigned_user_id', $user->getKey())
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('knowledge_article_id');

        $completionsByArticle = KnowledgeCompletion::query()
            ->where('user_id', $user->getKey())
            ->get()
            ->keyBy('knowledge_article_id');

        $courseCategoryIds = KnowledgeCourseAssignment::query()
            ->where('assigned_user_id', $user->getKey())
            ->pluck('knowledge_category_id');

        $courseArticleIds = $courseCategoryIds->isEmpty()
            ? collect()
            : KnowledgeArticle::query()
                ->whereHas(
                    'categories',
                    fn ($query) => $query->whereIn('knowledge_categories.id', $courseCategoryIds),
                )
                ->pluck('id');

        return KnowledgeArticle::query()
            ->where(function ($query) use ($assignmentsByArticle, $courseArticleIds): void {
                $query->where('is_public', true)
                    ->orWhereIn('id', $assignmentsByArticle->keys())
                    ->orWhereIn('id', $courseArticleIds);
            })
            ->orderBy('title')
            ->get()
            ->map(function (KnowledgeArticle $article) use ($assignmentsByArticle, $completionsByArticle, $courseArticleIds): array {
                $articleAssignments = $assignmentsByArticle->get($article->id);
                $pendingAssignment = $articleAssignments?->firstWhere('completed_at', null);
                $completedAssignment = $articleAssignments?->firstWhere('completed_at', '!==', null);
                $completion = $completionsByArticle->get($article->id);

                $completedAt = $completedAssignment?->completed_at ?? $completion?->completed_at;
                $dueAt = $pendingAssignment?->due_at ?? $completedAssignment?->due_at;
                $inCourse = $courseArticleIds->contains($article->id);

                return [
                    'id' => $article->id,
                    'name' => $article->title,
                    'url' => KnowledgeArticleResource::getUrl('view', ['record' => $article]),
                    'public' => (bool) $article->is_public,
                    'assigned' => $articleAssignments !== null,
                    'in_course' => $inCourse,
                    'availability' => collect([
                        $article->is_public ? 'Obligāts saturs' : null,
                        $articleAssignments !== null ? 'Piešķirts' : null,
                        $inCourse ? 'Kurss' : null,
                    ])->filter()->implode(' · '),
                    'due_at' => $dueAt,
                    'completed_at' => $completedAt,
                    'completed' => $completedAt !== null,
                    'overdue' => $completedAt === null && $dueAt !== null && $dueAt->isPast(),
                    'rating' => $completedAssignment?->rating ?? $completion?->rating,
                    'status' => $completedAt !== null ? 'Pabeigts' : 'Jāizpilda',
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     assigned: array<int, array<string, mixed>>,
     *     materials: array<int, array<string, mixed>>,
     *     completed: int,
     *     materials_completed: int
     * }
     */
    public function summary(User $user): array
    {
        $courses = $this->courses($user);
        $materials = $this->materials($user);

        return [
            'assigned' => $courses,
            'materials' => $materials,
            'completed' => count(array_filter($courses, fn (array $course): bool => $course['completed_at'] !== null)),
            'materials_completed' => count(array_filter($materials, fn (array $material): bool => $material['completed_at'] !== null)),
        ];
    }
}
