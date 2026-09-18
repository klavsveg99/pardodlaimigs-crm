<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Client;
use App\Models\CrmProperty;
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
        $user = auth()->user();
        $isAdmin = $user?->can('manage') ?? false;

        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $yearStart = now()->startOfYear();
        $yearEnd = now()->endOfYear();

        // "Aktīvie" = publicētie (Pārdošanā) visiem aģentiem kopā; apakšā
        // parādās pašreizējā aģenta paša aktīvie īpašumi.
        $activeProperties = CrmProperty::where('status', 'published')->count();
        $myActiveProperties = CrmProperty::where('status', 'published')
            ->where('owner_user_id', $user?->id)
            ->count();


        $totalClients = ($isAdmin ? Client::query() : Client::where('owner_user_id', $user?->id))->count();
        $newClientsThisMonth = (($isAdmin ? Client::query() : Client::where('owner_user_id', $user?->id)))
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->count();

        // Sold this month (portable: sold_at when present, else updated_at)
        $soldThisMonth = $this->soldInRange($monthStart, $monthEnd, $isAdmin);

        $monthCommission = (float) $soldThisMonth['commission'];
        $monthSoldCount = $soldThisMonth['count'];

        // Yearly sold for avg metrics
        $soldThisYear = $this->soldInRange($yearStart, $yearEnd, $isAdmin);
        $yearCount = (int) $soldThisYear['count'];
        $yearFinalValue = round($soldThisYear['final_price'], 2);

        $avgDealValue = $yearCount > 0
            ? round($soldThisYear['final_price'] / $yearCount, 2)
            : null;

        $stats = [
            Stat::make('Īpašumi pārdošanā uzņēmumā', $activeProperties)
                ->description('Mani īpašumi: '.$myActiveProperties)
                ->descriptionIcon('heroicon-o-home')
                ->color('success')
                ->url(\App\Filament\Admin\Resources\CrmPropertyResource::getUrl('index')),

            Stat::make($isAdmin ? 'Aktīvie klienti' : 'Mani klienti', $totalClients)
                ->description('Jauni šomēnes: '.$newClientsThisMonth)
                ->descriptionIcon('heroicon-o-users')
                ->color('info')
                ->url(\App\Filament\Admin\Resources\ClientResource::getUrl('index')),

            Stat::make('Šomēnes pārdoti', $monthSoldCount)
                ->description('Komisija: '.number_format($monthCommission, 2, ',', ' ').' €')
                ->descriptionIcon('heroicon-o-check-badge')
                ->color('primary')
                ->url(\App\Filament\Admin\Resources\CrmPropertyResource::getUrl('index')),

            // Adminam — visi pārdotie; aģentam — tikai paša (soldInRange jau
            // filtrē pēc owner_user_id, kad $isAdmin ir false).
            Stat::make('Vid. pārdošanas cena (gads)', $avgDealValue !== null ? number_format($avgDealValue, 0, ',', ' ').' €' : '—')
                ->description($avgDealValue !== null ? 'Kopā šogad: '.number_format($yearFinalValue, 0, ',', ' ').' € ('.$yearCount.' pārdoti)' : 'Nav pārdoto īpašumu šogad')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('secondary')
                ->url(\App\Filament\Admin\Resources\CrmPropertyResource::getUrl('index')),
        ];

        // Kapitalizācija = pašreizējā kopējā vērtība Pārdošanā esošajiem
        // īpašumiem (aģentam — paša īpašumi, adminam — visi kopā).
        $capitalization = ($isAdmin
            ? CrmProperty::query()
            : CrmProperty::query()->where('owner_user_id', $user?->id)
        )
            ->where('status', 'published')
            ->sum('price_eur');

        $stats[] = Stat::make('Kapitalizācija', number_format((float) $capitalization, 0, ',', ' ').' €')
            ->descriptionIcon('heroicon-o-banknotes')
            ->color('primary')
            ->url(\App\Filament\Admin\Resources\CrmPropertyResource::getUrl('index'));

        return $stats;
    }

    protected function soldInRange($start, $end, bool $forAdminOnly = true): array
    {
        $query = CrmProperty::query()
            ->where('status', 'sold')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('sold_at', [$start, $end])
                    ->orWhere(fn ($q2) => $q2
                        ->whereNull('sold_at')
                        ->whereBetween('updated_at', [$start, $end]));
            });

        if (! $forAdminOnly) {
            $query->where('owner_user_id', auth()->id());
        }

        $properties = $query->get(['sold_at', 'updated_at', 'commission_eur', 'final_price_eur', 'lead_source']);

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

}
