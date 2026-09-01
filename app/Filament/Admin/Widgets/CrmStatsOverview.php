<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Client;
use App\Models\CrmProperty;
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
        $overdueTasks = Task::whereNull('completed_at')->where('due_at', '<', now())->count();
        $lateViewings = Viewing::where('scheduled_at', '<', now())
            ->where('status', '!=', 'done')
            ->count();
        $activeProperties = CrmProperty::whereNotIn('status', ['sold'])->count();
        $openTasks = Task::whereNull('completed_at')->count();

        return [
            Stat::make('Aktīvie īpašumi', $activeProperties)
                ->descriptionIcon('heroicon-o-home')
                ->color('success'),
            Stat::make('Atvērtas apskates', Viewing::whereBetween('scheduled_at', [now()->startOfDay(), now()->endOfDay()])->count())
                ->description($lateViewings > 0 ? 'Nokavētas: '.$lateViewings : null)
                ->descriptionIcon($lateViewings > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-calendar-days')
                ->color($lateViewings > 0 ? 'warning' : 'info'),
            Stat::make('Atvērtie uzdevumi', $openTasks)
                ->description($overdueTasks > 0 ? 'Nokavētas: '.$overdueTasks : null)
                ->descriptionIcon($overdueTasks > 0 ? 'heroicon-o-exclamation-triangle' : null)
                ->color($overdueTasks > 0 ? 'warning' : 'secondary'),
        ];
    }
}
