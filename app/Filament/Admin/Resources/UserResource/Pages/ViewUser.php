<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\CrmProperty;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ViewUser extends ViewRecord
{
    private const PER_PAGE = 10;

    protected static string $resource = UserResource::class;

    protected string $view = 'filament.admin.resources.user-resource.pages.view-user';

    /**
     * Aģenta darba pārskata gads. Pēc noklusējuma tekošais gads.
     */
    public int $year = 0;

    public string $activeSearch = '';

    public string $activeCategory = '';

    public string $activeStatus = '';

    public string $activeCategoryDraft = '';

    public string $activeStatusDraft = '';

    public int $activePage = 1;

    public string $soldSearch = '';

    public string $soldSource = '';

    public string $soldSourceDraft = '';

    public int $soldPage = 1;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        $this->year = $this->resolveYear((int) request()->query('year', now()->year));
    }

    public function getTitle(): string
    {
        return 'Skatīt aģentu';
    }

    public function getHeading(): string
    {
        return (string) $this->record->name;
    }

    public function getSubheading(): ?string
    {
        return collect([$this->record->role_label, $this->record->email])
            ->filter()
            ->implode(' · ') ?: null;
    }

    public function updatedYear(): void
    {
        $this->year = $this->resolveYear((int) $this->year);
        $this->activePage = 1;
        $this->soldPage = 1;
    }

    public function updatedActiveSearch(): void
    {
        $this->activePage = 1;
    }

    public function updatedActiveCategory(): void
    {
        $this->activePage = 1;
    }

    public function updatedActiveStatus(): void
    {
        $this->activePage = 1;
    }

    public function updatedSoldSearch(): void
    {
        $this->soldPage = 1;
    }

    public function updatedSoldSource(): void
    {
        $this->soldPage = 1;
    }

    public function applyActiveFilters(): void
    {
        $this->activeCategory = $this->activeCategoryDraft;
        $this->activeStatus = $this->activeStatusDraft;
        $this->activePage = 1;
    }

    public function applySoldFilters(): void
    {
        $this->soldSource = $this->soldSourceDraft;
        $this->soldPage = 1;
    }

    public function clearActiveFilters(): void
    {
        $this->activeCategory = '';
        $this->activeStatus = '';
        $this->activeCategoryDraft = '';
        $this->activeStatusDraft = '';
        $this->activePage = 1;
    }

    public function clearSoldFilters(): void
    {
        $this->soldSource = '';
        $this->soldSourceDraft = '';
        $this->soldPage = 1;
    }

    public function setActivePage(int $page): void
    {
        $this->activePage = max(1, $page);
    }

    public function setSoldPage(int $page): void
    {
        $this->soldPage = max(1, $page);
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
     * Viss aģenta profila pārskatam nepieciešamais vienā masīvā.
     *
     * @return array<string, mixed>
     */
    public function getReport(): array
    {
        $agentId = (int) $this->record->getKey();

        $activeAll = CrmProperty::query()
            ->where('owner_user_id', $agentId)
            ->whereIn('status', ['published', 'reserved'])
            ->orderByDesc('created_at')
            ->get();

        $activeFiltered = $activeAll
            ->filter(fn (CrmProperty $property): bool => $this->matchesSearch($property, $this->activeSearch)
                && ($this->activeCategory === '' || $property->category === $this->activeCategory)
                && ($this->activeStatus === '' || $property->status === $this->activeStatus))
            ->values();

        $sold = CrmProperty::query()
            ->where('owner_user_id', $agentId)
            ->where('status', 'sold')
            ->whereNotNull('sold_at')
            ->whereBetween('sold_at', [
                Carbon::create($this->year, 1, 1)->startOfDay(),
                Carbon::create($this->year, 12, 31)->endOfDay(),
            ])
            ->orderByDesc('sold_at')
            ->get();

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

        $totalCommission = 0.0;
        $totalPrice = 0.0;

        foreach ($sold as $property) {
            $month = (int) $property->sold_at->format('n');
            $monthly[$month]['count']++;
            $totalPrice += (float) ($property->final_price_eur ?? 0);

            if ($property->commission_eur === null) {
                continue;
            }

            $commission = (float) $property->commission_eur;
            $monthly[$month]['commission'] += $commission;
            $totalCommission += $commission;
        }

        $allSold = $sold->concat($undated);

        $soldFiltered = $allSold
            ->filter(fn (CrmProperty $property): bool => $this->matchesSearch($property, $this->soldSearch)
                && ($this->soldSource === '' || $property->lead_source === $this->soldSource))
            ->values();

        $soldDatedFiltered = $soldFiltered->filter(fn (CrmProperty $property): bool => $property->sold_at !== null);

        return [
            'active' => $this->paginateCollection($activeFiltered, $this->activePage, 'activePage'),
            'active_total' => $activeAll->count(),
            'sold' => $this->paginateCollection($soldFiltered, $this->soldPage, 'soldPage'),
            'monthly' => $monthly,
            'sold_count' => $sold->count(),
            'total_commission' => $totalCommission,
            'total_price' => $totalPrice,
            'table_total_commission' => $soldDatedFiltered
                ->filter(fn (CrmProperty $property): bool => $property->commission_eur !== null)
                ->sum(fn (CrmProperty $property): float => (float) $property->commission_eur),
            'table_total_price' => $soldDatedFiltered
                ->sum(fn (CrmProperty $property): float => (float) ($property->final_price_eur ?? 0)),
            'missing_commission' => $allSold->whereNull('commission_eur')->count(),
            'undated_count' => $undated->count(),
        ];
    }

    private function matchesSearch(CrmProperty $property, string $search): bool
    {
        $search = mb_strtolower(trim($search));

        if ($search === '') {
            return true;
        }

        $haystack = mb_strtolower(implode(' ', array_filter([
            $property->title,
            $property->city,
            $property->address,
        ])));

        return str_contains($haystack, $search);
    }

    /**
     * @param  Collection<int, CrmProperty>  $items
     */
    private function paginateCollection(Collection $items, int $page, string $pageName): LengthAwarePaginator
    {
        $total = $items->count();
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        $currentPage = min(max(1, $page), $lastPage);

        return new LengthAwarePaginator(
            $items->forPage($currentPage, self::PER_PAGE)->values(),
            $total,
            self::PER_PAGE,
            $currentPage,
            ['pageName' => $pageName],
        );
    }

    private function resolveYear(int $year): int
    {
        $options = $this->getYearOptions();

        return array_key_exists($year, $options) ? $year : (int) array_key_first($options);
    }
}
