<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\CrmProperty;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeCourseAssignment;
use App\Services\KnowledgeProgress;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Carbon;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected string $view = 'filament.admin.resources.user-resource.pages.view-user';

    /**
     * Aģenta darba pārskata gads. Pēc noklusējuma tekošais gads.
     */
    public int $year = 0;

    /**
     * Izvēlētais mēnesis (0 = visi gada mēneši).
     */
    public int $month = 0;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->year = $this->resolveYear((int) request()->query('year', now()->year));
    }

    public function getTitle(): string
    {
        return match (true) {
            $this->getRecord()?->isStudent() => 'Skatīt studentu',
            $this->getRecord()?->isAdmin() => 'Skatīt administratoru',
            default => 'Skatīt aģentu',
        };
    }

    public function updatedYear(): void
    {
        $this->year = $this->resolveYear((int) $this->year);
    }

    public function updatedMonth(): void
    {
        $this->month = min(12, max(0, (int) $this->month));
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()->label('Rediģēt'),
        ];
    }

    /**
     * Gadi, kurus var izvēlēties pārskatā: no aģenta pirmā pārdotā objekta
     * līdz tekošajam gadam (ieskaitot).
     *
     * @return array<int, int>
     */
    public function getYearOptions(): array
    {
        $years = CrmProperty::query()
            ->where('owner_user_id', $this->record->getKey())
            ->where('status', 'sold')
            ->whereNotNull('sold_at')
            ->pluck('sold_at')
            ->map(fn ($date): int => (int) Carbon::parse($date)->format('Y'))
            ->push((int) now()->year);

        $min = (int) $years->min();
        $max = (int) $years->max();

        $range = range($max, $min);

        return array_combine($range, $range);
    }

    /**
     * Kopsavilkuma un mēnešu tabulas dati. Objektu sarakstus rāda atsevišķi
     * Filament tabulu widgeti.
     *
     * @return array<string, mixed>
     */
    public function getReport(): array
    {
        $agentId = (int) $this->record->getKey();

        $yearStart = Carbon::create($this->year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($this->year, 12, 31)->endOfDay();

        $soldYear = CrmProperty::query()
            ->where('owner_user_id', $agentId)
            ->where('status', 'sold')
            ->whereNotNull('sold_at')
            ->whereBetween('sold_at', [$yearStart, $yearEnd])
            ->orderByDesc('sold_at')
            ->get();

        $sold = $this->month > 0
            ? $soldYear->filter(fn (CrmProperty $property): bool => (int) $property->sold_at->format('n') === $this->month)->values()
            : $soldYear;

        // Pārdotie objekti bez darījuma datuma: tos nevar ieskaitīt nevienā
        // mēnesī, bet tie jāparāda, lai trūkstošie dati nepazustu.
        $undated = CrmProperty::query()
            ->where('owner_user_id', $agentId)
            ->where('status', 'sold')
            ->whereNull('sold_at')
            ->orderByDesc('updated_at')
            ->get();

        $monthly = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthly[$month] = ['count' => 0, 'commission' => 0.0];
        }

        $yearTotalCommission = 0.0;

        foreach ($soldYear as $property) {
            $month = (int) $property->sold_at->format('n');
            $monthly[$month]['count']++;

            if ($property->commission_eur === null) {
                continue;
            }

            $commission = (float) $property->commission_eur;
            $monthly[$month]['commission'] += $commission;
            $yearTotalCommission += $commission;
        }

        $totalCommission = $sold
            ->filter(fn (CrmProperty $property): bool => $property->commission_eur !== null)
            ->sum(fn (CrmProperty $property): float => (float) $property->commission_eur);

        $allSold = $soldYear->concat($undated);

        return [
            'sold_count' => $sold->count(),
            'total_commission' => $totalCommission,
            'year_sold_count' => $soldYear->count(),
            'year_total_commission' => $yearTotalCommission,
            'monthly' => $monthly,
            'missing_commission' => $allSold->whereNull('commission_eur')->count(),
            'undated_count' => $undated->count(),
        ];
    }

    /**
     * Zināšanu bāzes kopsavilkums lietotāja skatam: piešķirtie kursi un
     * visu kursu gaita šim lietotājam.
     *
     * @return array{assigned: array<int, array<string, mixed>>, all: array<int, array<string, mixed>>, completed: int}
     */
    public function getKnowledgeRecap(): array
    {
        $user = $this->record;
        $progress = app(KnowledgeProgress::class);

        $assigned = KnowledgeCourseAssignment::query()
            ->with('category')
            ->where('assigned_user_id', $user->getKey())
            ->get()
            ->map(function (KnowledgeCourseAssignment $assignment) use ($progress, $user): array {
                $stats = $assignment->category
                    ? $progress->courseProgress($user, $assignment->category)
                    : ['total' => 0, 'completed' => 0, 'percent' => 0];

                return [
                    'name' => $assignment->category?->name ?? 'Dzēsts kurss',
                    'stats' => $stats,
                    'due_at' => $assignment->due_at,
                    'completed_at' => $assignment->completed_at,
                    'rating' => $assignment->rating,
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();

        $assignedCategoryIds = KnowledgeCourseAssignment::query()
            ->where('assigned_user_id', $user->getKey())
            ->pluck('knowledge_category_id')
            ->all();

        $all = KnowledgeCategory::query()
            ->orderBy('name')
            ->get()
            ->map(fn (KnowledgeCategory $category): array => [
                'name' => $category->name,
                'stats' => $progress->courseProgress($user, $category),
                'assigned' => in_array($category->id, $assignedCategoryIds, true),
            ])
            ->all();

        return [
            'assigned' => $assigned,
            'all' => $all,
            'completed' => count(array_filter($assigned, fn (array $course): bool => $course['completed_at'] !== null)),
        ];
    }

    private function resolveYear(int $year): int
    {
        $options = $this->getYearOptions();

        return array_key_exists($year, $options) ? $year : (int) array_key_first($options);
    }
}
