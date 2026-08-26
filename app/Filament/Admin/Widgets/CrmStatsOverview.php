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
        $overdueTasks = Task::whereNull('completed_at')->where('due_at', '<', now())->count();
        $lateViewings = Viewing::where('scheduled_at', '<', now())
            ->where('status', '!=', 'done')
            ->count();

        return [
            Stat::make('Aktīvie klienti', Client::whereNull('gdpr_erased_at')->count())
                ->descriptionIcon('heroicon-o-user-group')
                ->color('success'),
            Stat::make('Atvērtie darījumi', Deal::where('stage', '!=', 'pardots')->count())
                ->descriptionIcon('heroicon-o-currency-euro')
                ->color('primary'),
            Stat::make('Atvērtas apskates', Viewing::whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])->count())
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info')
                ->description($lateViewings > 0 ? 'Uzstādejas: '.$lateViewings : ''),
            Stat::make('Nokavētie uzdevumi', $overdueTasks)
                ->description($overdueTasks > 0 ? 'Gaida darītāju' : '')
                ->descriptionIcon($overdueTasks > 0 ? 'heroicon-o-exclamation-triangle' : null)
                ->color($overdueTasks > 0 ? 'warning' : 'secondary'),
        ];
    }
}
