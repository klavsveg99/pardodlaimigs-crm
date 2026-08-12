<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Client;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Viewing;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Aktīvie klienti', Client::whereNull('gdpr_erased_at')->count())
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success'),
            Stat::make('Atvērtie darījumi', Deal::whereNotIn('stage', ['closed_won', 'closed_lost'])->count())
                ->descriptionIcon('heroicon-o-currency-euro')
                ->color('primary'),
            Stat::make('Šodienas apskates', Viewing::whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])->count())
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),
            Stat::make('Nokavētie uzdevumi', Task::whereNull('completed_at')->where('due_at', '<', now())->count())
                ->description('Gaida darītāju')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning'),
        ];
    }
}
