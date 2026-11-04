<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Filament\Admin\Resources\KnowledgeArticleResource;
use App\Models\KnowledgeAssignment;
use App\Models\KnowledgeComment;
use App\Models\KnowledgeCompletion;
use App\Models\KnowledgeCourseAssignment;
use App\Services\KnowledgeProgress;
use Livewire\Component;

/**
 * Aģenta kursu sadaļa /knowledge lapā. Katrs kurss ir kategorija ar
 * piešķīrumu, tā gaita rāda pabeigto materiālu skaitu un termiņu.
 */
class KnowledgeCourses extends Component
{
    public function render()
    {
        $user = auth()->user();

        if (! $user || $user->can('manage') || $user->isPhoto()) {
            return view('livewire.knowledge-courses', ['courses' => []]);
        }

        $progress = app(KnowledgeProgress::class);

        $assignments = KnowledgeCourseAssignment::query()
            ->with([
                'category.articles' => fn ($query) => $query
                    ->with([
                        'assignments' => fn ($assignment) => $assignment->where('assigned_user_id', $user->id),
                        'completions' => fn ($completion) => $completion->where('user_id', $user->id),
                    ])
                    ->orderBy('title'),
            ])
            ->where('assigned_user_id', $user->id)
            ->get()
            ->sortBy(fn (KnowledgeCourseAssignment $assignment): int => $assignment->due_at?->getTimestamp() ?? PHP_INT_MAX)
            ->values();

        $courses = $assignments->map(function (KnowledgeCourseAssignment $assignment) use ($progress, $user): array {
            $category = $assignment->category;

            if (! $category) {
                return [];
            }

            $assignment = $progress->syncCourseCompletion($user, $category) ?? $assignment;
            $stats = $progress->courseProgress($user, $category);

            $articles = $category->articles->map(function ($article): array {
                $completed = $article->assignments->contains(
                    fn (KnowledgeAssignment $item): bool => $item->completed_at !== null,
                ) || $article->completions->contains(
                    fn (KnowledgeCompletion $item): bool => $item->completed_at !== null,
                );

                return [
                    'title' => $article->title,
                    'completed' => $completed,
                    'url' => KnowledgeArticleResource::getUrl('view', ['record' => $article]),
                ];
            })->all();

            return [
                'assignment' => $assignment,
                'category' => $category,
                'progress' => $stats,
                'articles' => $articles,
                'due_at' => $assignment->due_at,
                'completed' => $assignment->completed_at !== null,
                'is_overdue' => ! $assignment->completed_at
                    && $assignment->due_at !== null
                    && $assignment->due_at->isPast(),
                'rating' => $assignment->rating,
                'comment_count' => KnowledgeComment::query()
                    ->where('knowledge_category_id', $category->id)
                    ->count(),
            ];
        })->filter()->values()->all();

        return view('livewire.knowledge-courses', [
            'courses' => $courses,
        ]);
    }
}
