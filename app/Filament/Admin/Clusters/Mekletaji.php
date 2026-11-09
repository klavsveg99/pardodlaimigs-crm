<?php

declare(strict_types=1);

namespace App\Filament\Admin\Clusters;

use App\Models\ScrapedListing;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use UnitEnum;

/**
 * Saved searches and the listings they found live under one "Meklētāji"
 * entry: the cluster renders its resources as sub-navigation tabs.
 */
class Mekletaji extends Cluster
{
    protected static ?string $navigationLabel = 'Meklētāji';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static string|UnitEnum|null $navigationGroup = 'CRM';

    protected static ?int $navigationSort = 40;

    protected static ?string $clusterBreadcrumb = 'Meklētāji';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    /** New listings found by the saved searches, shown on the combined entry. */
    public static function getNavigationBadge(): ?string
    {
        $count = ScrapedListing::query()
            ->where('status', 'new')
            ->when(
                ! auth()->user()?->can('manage'),
                fn ($query) => $query->where('user_id', auth()->id()),
            )
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'gray';
    }
}
