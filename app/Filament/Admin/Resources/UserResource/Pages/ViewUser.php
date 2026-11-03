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

        $active = CrmProperty::query()
            ->where('owner_user_id', $agentId)
            ->whereIn('status', ['published', 'reserved'])
            ->orderByDesc('created_at')
            ->get();

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

        return [
            'active' => $active,
            'sold' => $sold,
            'undated' => $undated,
            'monthly' => $monthly,
            'sold_count' => $sold->count(),
            'total_commission' => $totalCommission,
            'total_price' => $totalPrice,
            'missing_commission' => $allSold->whereNull('commission_eur')->count(),
            'missing_percent' => $allSold->whereNull('agent_percent')->count(),
            'undated_count' => $undated->count(),
        ];
    }

    private function resolveYear(int $year): int
    {
        $options = $this->getYearOptions();

        return array_key_exists($year, $options) ? $year : (int) array_key_first($options);
    }
}
