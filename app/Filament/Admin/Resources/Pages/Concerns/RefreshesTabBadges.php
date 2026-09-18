<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Concerns;

/**
 * Filament's list pages cache the tab objects — and therefore their badge
 * counts — for the whole request via HasTabs::getCachedTabs(). The cache gets
 * populated while the table resolves records, before an action runs, so the
 * counters ("Aktīvie", "Dzēstie", …) only moved after a full page navigation.
 *
 * Clearing the caches right before each render makes the tab component —
 * and getTabs() — rebuild against the current data, so badges update after
 * row/bulk actions, inline status changes, etc.
 */
trait RefreshesTabBadges
{
    public function renderingInteractsWithSchemas(): void
    {
        // Drop the tab objects and the schema that embedded them, so the
        // render rebuilds the badges from the current database state.
        unset($this->cachedTabs);
        $this->cachedSchemas = [];

        parent::renderingInteractsWithSchemas();
    }
}
