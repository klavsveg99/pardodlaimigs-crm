<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use App\Models\CrmProperty;
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
        return 'Skatīt aģentu';
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

        $activeTotal = CrmProperty::query()
            ->where('owner_user_id', $agentId)
            ->whereIn('status', ['published', 'reserved'])
            ->count();

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
            'active_total' => $activeTotal,
            'sold_count' => $sold->count(),
            'total_commission' => $totalCommission,
            'year_sold_count' => $soldYear->count(),
            'year_total_commission' => $yearTotalCommission,
            'monthly' => $monthly,
            'missing_commission' => $allSold->whereNull('commission_eur')->count(),
            'undated_count' => $undated->count(),
        ];
    }

    private function resolveYear(int $year): int
    {
        $options = $this->getYearOptions();

        return array_key_exists($year, $options) ? $year : (int) array_key_first($options);
    }
}
