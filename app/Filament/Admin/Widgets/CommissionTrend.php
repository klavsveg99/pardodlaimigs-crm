<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\CrmProperty;
use Filament\Widgets\ChartWidget;

class CommissionTrend extends ChartWidget
{
    protected ?string $heading = 'Komisijas tendence';

    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = now()->subMonths(11)->startOfMonth();
        $end = now()->endOfMonth();

        // Sold in period (use sold_at when set, otherwise updated_at)
        $properties = CrmProperty::query()
            ->where('status', 'sold')
            ->whereNotNull('owner_user_id')
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('sold_at', [$start, $end])
                    ->orWhere(fn ($q2) => $q2
                        ->whereNull('sold_at')
                        ->whereBetween('updated_at', [$start, $end]));
            })
            ->get(['sold_at', 'updated_at', 'commission_eur', 'lead_source']);

        $monthly = [];
        foreach ($properties as $property) {
            $at = $property->sold_at ?? $property->updated_at;
            if (! $at) {
                continue;
            }
            $key = $at->format('Y-m');
            // External lead => agency reserves 20% of commission for the agent/lead
            $commission = (float) $property->commission_eur;
            if ($property->lead_source === 'external') {
                $commission = $commission * 0.2;
            }
            $monthly[$key] = ($monthly[$key] ?? 0) + $commission;
        }

        $labels = [];
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->locale('lv')->translatedFormat('F Y');
            $data[] = round($monthly[$key] ?? 0, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => '',
                    'data' => $data,
                    'borderColor' => '#285854',
                    'backgroundColor' => 'rgba(40,88,84,0.1)',
                    'fill' => true,
                    'tension' => 0.2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'maintainAspectRatio' => false,
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'display' => false,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return value + " €"; }',
                    ],
                ],
            ],
        ];
    }
}
