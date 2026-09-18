<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\WpformEntryResource\Pages;

use App\Filament\Admin\Resources\Pages\Concerns\RefreshesTabBadges;
use App\Filament\Admin\Resources\WpformEntryResource;
use App\Models\WpformEntry;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWpformEntries extends ListRecords
{
    use RefreshesTabBadges;

    protected static string $resource = WpformEntryResource::class;

    public function getTabs(): array
    {
        $active = fn (Builder $query): Builder => $query
            ->where(fn (Builder $q): Builder => $q->whereNull('status')->orWhere('status', '!=', 'deleted'));

        return [
            'active' => Tab::make('Aktīvie')
                ->modifyQueryUsing($active)
                ->badge($active(WpformEntry::query())->count()),
            'deleted' => Tab::make('Dzēstie')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'deleted'))
                ->badge(WpformEntry::where('status', 'deleted')->count()),
        ];
    }
}
