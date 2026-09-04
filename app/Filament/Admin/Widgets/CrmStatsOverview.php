<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Client;
use App\Models\CrmProperty;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Viewing;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getColumns(): int | array | null
    {
        return [
            'md' => 2,
            'lg' => 3,
        ];
    }

    protected function getStats(): array
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $yearStart = now()->startOfYear();
        $yearEnd = now()->endOfYear();

        $overdueTasks = Task::whereNull('completed_at')->where('due_at', '<', now())->count();
        $lateViewings = Viewing::where('scheduled_at', '<', now())
            ->where('status', '!=', 'done')
            ->count();
        $activeProperties = CrmProperty::whereNotIn('status', ['sold'])->count();
        $openTasks = Task::whereNull('completed_at')->count();
        $totalClients = Client::count();

        // Sold this month (portable: sold_at when present, else updated_at)
        $soldThisMonth = $this->soldInRange($monthStart, $monthEnd);

        $monthCommission = (float) $soldThisMonth['commission'];
        $monthSoldCount = $soldThisMonth['count'];

        // Active deals (not sold) — potential commission estimate
        $activeDeals = Deal::where('stage', '!=', 'pardots')
            ->whereNull('closed_at')
            ->where('value_eur', '>', 0)
            ->pluck('value_eur');

        // Yearly sold for avg metrics
        $soldThisYear = $this->soldInRange($yearStart, $yearEnd);
        $yearCommission = round($soldThisYear['commission'], 2);
        $yearCount = (int) $soldThisYear['count'];
        $yearFinalValue = round($soldThisYear['final_price'], 2);

        $avgDealValue = $yearCount > 0
            ? round($soldThisYear['final_price'] / $yearCount, 2)
            : null;

        // Average commission percent across sold properties (per-property, weighted)
        $avgCommissionPercent = $this->averageCommissionPercent($yearStart, $yearEnd);

        $potentialCommission = null;
        if ($activeDeals->isNotEmpty()) {
            $sumValue = (float) $activeDeals->sum();
            if ($avgCommissionPercent !== null) {
                $potentialCommission = round($sumValue * $avgCommissionPercent / 100, 2);
            }
        }

        $avgSellDays = $this->averageSellDays($yearStart, $yearEnd);

        $stats = [
            Stat::make('Aktīvie īpašumi', $activeProperties)
                ->descriptionIcon('heroicon-o-home')
                ->color('success'),

            Stat::make('Klienti (kopā)', $totalClients)
                ->description('Jauni šomēnes: '.Client::whereBetween('created_at', [$monthStart, $monthEnd])->count())
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make('Šomēnes pārdoti', $monthSoldCount)
                ->description('Komisija: '.number_format($monthCommission, 2, ',', ' ').' €')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('primary'),


        ];

        $stats[] = Stat::make('Vid. darījuma vērtība (gads)', $avgDealValue !== null ? number_format($avgDealValue, 0, ',', ' ').' €' : '—')
            ->description($avgDealValue !== null ? 'Kopā šogad: '.number_format($yearFinalValue, 0, ',', ' ').' € ('.$yearCount.' pārdoti)' : 'Nav pārdoto īpašumu šogad')
            ->descriptionIcon('heroicon-o-banknotes')
            ->color('secondary');



        $stats[] = Stat::make('Atvērtas apskates šodien', Viewing::whereBetween('scheduled_at', [$todayStart, $todayEnd])->count())
            ->description($lateViewings > 0 ? 'Nokavētas: '.$lateViewings : null)
            ->descriptionIcon($lateViewings > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-calendar-days')
            ->color($lateViewings > 0 ? 'warning' : 'info');

        $stats[] = Stat::make('Atvērtie uzdevumi', $openTasks)
            ->description($overdueTasks > 0 ? 'Nokavētas: '.$overdueTasks : null)
            ->descriptionIcon($overdueTasks > 0 ? 'heroicon-o-exclamation-triangle' : null)
            ->color($overdueTasks > 0 ? 'warning' : 'secondary');

        return $stats;
    }

    protected function soldInRange($start, $end): array
    {
        $properties = CrmProperty::query()
            ->where('status', 'sold')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('sold_at', [$start, $end])
                    ->orWhere(fn ($q2) => $q2
                        ->whereNull('sold_at')
                        ->whereBetween('updated_at', [$start, $end]));
            })
            ->get(['sold_at', 'updated_at', 'commission_eur', 'final_price_eur', 'lead_source']);

        $commission = 0;
        $finalPrice = 0;
        $count = 0;
        foreach ($properties as $property) {
            $at = $property->sold_at ?? $property->updated_at;
            if (! $at) {
                continue;
            }
            $c = (float) $property->commission_eur;
            if ($property->lead_source === 'external') {
                $c = $c * 0.2;
            }
            $commission += $c;
            $finalPrice += (float) $property->final_price_eur;
            $count++;
        }

        return ['commission' => round($commission, 2), 'final_price' => $finalPrice, 'count' => $count];
    }

    protected function averageCommissionPercent($start, $end): ?float
    {
        $properties = CrmProperty::query()
            ->where('status', 'sold')
            ->where('final_price_eur', '>', 0)
            ->where('commission_eur', '>', 0)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('sold_at', [$start, $end])
                    ->orWhere(fn ($q2) => $q2
                        ->whereNull('sold_at')
                        ->whereBetween('updated_at', [$start, $end]));
            })
            ->get(['commission_eur', 'final_price_eur']);

        $totalComm = 0;
        $totalFinal = 0;
        foreach ($properties as $property) {
            $totalComm += (float) $property->commission_eur;
            $totalFinal += (float) $property->final_price_eur;
        }

        if ($totalFinal <= 0) {
            return null;
        }

        return round($totalComm / $totalFinal * 100, 2);
    }

    protected function averageSellDays($start, $end): ?int
    {
        $properties = CrmProperty::query()
            ->where('status', 'sold')
            ->whereNotNull('created_at')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('sold_at', [$start, $end])
                    ->orWhere(fn ($q2) => $q2
                        ->whereNull('sold_at')
                        ->whereBetween('updated_at', [$start, $end]));
            })
            ->get(['created_at', 'sold_at', 'updated_at']);

        $totalDays = 0;
        $count = 0;
        foreach ($properties as $property) {
            $soldAt = $property->sold_at ?? $property->updated_at;
            if (! $soldAt || ! $property->created_at) {
                continue;
            }
            $days = (int) floor(abs($soldAt->getTimestamp() - $property->created_at->getTimestamp()) / 86400);
            $totalDays += $days;
            $count++;
        }

        if ($count === 0) {
            return null;
        }

        return (int) round($totalDays / $count);
    }
}
